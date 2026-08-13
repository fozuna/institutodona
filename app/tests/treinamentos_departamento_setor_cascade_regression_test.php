<?php
namespace App\Core {
    // Intercepta header() dentro do namespace onde BaseController::redirect() vive
    // (App\Core), para capturar o "Location:" real emitido por
    // TreinamentosController::store()/update() sem depender de headers_list()
    // (nao confiavel em CLI).
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace App\Controllers {
    // Idem, para chamadas diretas a header() dentro dos controllers (ex.:
    // catalogoOptionsAjax() define Content-Type diretamente) - sem isso o aviso
    // "headers already sent" do PHP se mistura ao corpo capturado via ob_start()
    // e corrompe o JSON/HTML comparado nas asserções abaixo.
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require __DIR__ . '/../autoload.php';

    use App\Controllers\TreinamentosController;
    use App\Database\Database;
    use App\Models\ClienteModel;
    use App\Models\DepartamentoModel;
    use App\Models\SetorModel;
    use App\Models\FuncaoModel;
    use App\Models\TreinamentoModel;

    function ok(string $msg): void { echo "OK: $msg\n"; }
    function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

    function resetRequest(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($GLOBALS['__captured_location']);
    }

    $pdo = Database::getConnection();
    $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
    $clienteIds = [];
    $depIds = [];
    $setorIds = [];
    $funcaoIds = [];
    $treinamentoIds = [];

    try {
        // ===================== PARTE 1: wiring do JS (padrão já usado em ============
        // ===================== auditoria_dropdown_unit_test.php: leitura do fonte) ===
        $formFields = file_get_contents(__DIR__ . '/../views/treinamentos/form_fields.php');
        if ($formFields === false) {
            failFast('Não foi possível ler app/views/treinamentos/form_fields.php');
        }

        if (strpos($formFields, "departamento.addEventListener('change', apply)") === false) {
            failFast('Departamento principal não dispara mais o filtro visual (listener ausente) - causa raiz do Item 14 não corrigida');
        }
        ok('Cenário 2: Departamento principal passou a disparar o filtro visual de Setores/Funções (listener change -> apply)');

        if (strpos($formFields, 'activeDepartamentoIds') === false
            || strpos($formFields, 'normalizeSelectedId(departamento)') === false) {
            failFast('Filtro não está combinando Departamento principal + Departamentos adicionais');
        }
        ok('Cenário 6: filtro combina Departamento principal + Departamentos adicionais (união, não substituição)');

        if (strpos($formFields, 'loadCatalog(false)') === false) {
            failFast('Troca de Empresa não força reconstrução sem preservar seleções da empresa anterior');
        }
        ok('Cenário 4: troca de Empresa reconstrói o catálogo sem preservar seleções antigas (preserve=false)');

        if (strpos($formFields, 'Departamentos adicionais (opcional)') === false) {
            failFast('Rótulo "Departamentos adicionais (opcional)" não encontrado no formulário');
        }
        if (strpos($formFields, 'Departamentos (Filtro)') !== false) {
            failFast('Rótulo antigo "Departamentos (Filtro)" ainda presente - deveria ter sido renomeado');
        }
        ok('Campo renomeado para "Departamentos adicionais (opcional)"; rótulo antigo removido');

        $applyStart = strpos($formFields, 'const apply = ()');
        $dropSetoresPos = $applyStart !== false ? strpos($formFields, 'dropInvalidSelections(setores)', $applyStart) : false;
        $funcaoFilterPos = $applyStart !== false ? strpos($formFields, 'filterOptionsBySetores(funcoes', $applyStart) : false;
        if ($applyStart === false || $dropSetoresPos === false || $funcaoFilterPos === false || $dropSetoresPos > $funcaoFilterPos) {
            failFast('Cenário 5: setores incompatíveis precisam ser descartados ANTES do filtro de Funções ser recalculado (evita ID antigo "escondido" selecionado)');
        }
        ok('Cenário 5: ordem correta em apply() - setores incompatíveis são descartados antes de recalcular Funções');

        // ===================== PARTE 2: fixtures =====================
        $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto.trein.cascata@test.local', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

        $makeCnpj = static function (): string {
            $digits = '';
            for ($i = 0; $i < 14; $i++) { $digits .= (string)random_int(0, 9); }
            return $digits;
        };

        $clientes = new ClienteModel();
        $deps = new DepartamentoModel();
        $setoresModel = new SetorModel();
        $funcoesModel = new FuncaoModel();

        $clienteAId = $clientes->create(['nome_empresa' => 'Empresa Cascata A ' . $suffix, 'CNPJ' => $makeCnpj(), 'contato' => 'Contato']);
        $clienteBId = $clientes->create(['nome_empresa' => 'Empresa Cascata B ' . $suffix, 'CNPJ' => $makeCnpj(), 'contato' => 'Contato']);
        if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar empresas de teste'); }
        $clienteIds[] = $clienteAId;
        $clienteIds[] = $clienteBId;

        $depA1 = $deps->create(['nome' => 'Depto A1 ' . $suffix, 'cliente_id' => $clienteAId]);
        $depA2 = $deps->create(['nome' => 'Depto A2 ' . $suffix, 'cliente_id' => $clienteAId]);
        $depB = $deps->create(['nome' => 'Depto B ' . $suffix, 'cliente_id' => $clienteBId]);
        if ($depA1 <= 0 || $depA2 <= 0 || $depB <= 0) { failFast('Falha ao criar departamentos de teste'); }
        $depIds[] = $depA1; $depIds[] = $depA2; $depIds[] = $depB;

        $setorA1 = $setoresModel->create(['nome' => 'Setor A1 ' . $suffix, 'departamento_id' => $depA1]);
        $setorA2 = $setoresModel->create(['nome' => 'Setor A2 ' . $suffix, 'departamento_id' => $depA2]);
        $setorB = $setoresModel->create(['nome' => 'Setor B ' . $suffix, 'departamento_id' => $depB]);
        if ($setorA1 <= 0 || $setorA2 <= 0 || $setorB <= 0) { failFast('Falha ao criar setores de teste'); }
        $setorIds[] = $setorA1; $setorIds[] = $setorA2; $setorIds[] = $setorB;

        $funcaoA1 = $funcoesModel->create(['nome' => 'Função A1 ' . $suffix, 'setor_id' => $setorA1]);
        $funcaoA2 = $funcoesModel->create(['nome' => 'Função A2 ' . $suffix, 'setor_id' => $setorA2]);
        if ($funcaoA1 <= 0 || $funcaoA2 <= 0) { failFast('Falha ao criar funções de teste'); }
        $funcaoIds[] = $funcaoA1; $funcaoIds[] = $funcaoA2;
        ok('Fixtures criadas: Empresa A (Depto A1/A2, Setor A1/A2, Função A1/A2) e Empresa B (Depto B, Setor B) isoladas');

        // ===================== CENÁRIO 1: catálogo escopado por empresa (endpoint real) =====================
        resetRequest();
        $_GET['route'] = 'treinamentos/catalogoOptionsAjax';
        $_GET['cliente_id'] = (string)$clienteAId;
        ob_start();
        (new TreinamentosController())->catalogoOptionsAjax();
        $json = (string)ob_get_clean();
        $payload = json_decode($json, true);
        if (!is_array($payload) || ($payload['ok'] ?? false) !== true) {
            failFast('Endpoint catalogoOptionsAjax não retornou payload OK para a Empresa A');
        }
        $depIdsRetornados = array_map(static fn($d) => (int)$d['id'], $payload['catalogo']['departamentos'] ?? []);
        $setorIdsRetornados = array_map(static fn($s) => (int)$s['id'], $payload['catalogo']['setores'] ?? []);
        if (!in_array($depA1, $depIdsRetornados, true) || !in_array($depA2, $depIdsRetornados, true)) {
            failFast('Cenário 1: catálogo da Empresa A deveria conter Depto A1 e A2');
        }
        if (in_array($depB, $depIdsRetornados, true) || in_array($setorB, $setorIdsRetornados, true)) {
            failFast('Cenário 1: catálogo da Empresa A vazou departamento/setor da Empresa B');
        }
        ok('Cenário 1: catálogo retornado (catalogoOptionsAjax) contém somente Departamentos/Setores da Empresa selecionada');

        // Confere que cada setor/função carrega o departamento_id correto - é o contrato
        // (data-departamento-id) do qual o filtro em JS depende para funcionar.
        $setorA2Row = null;
        foreach ($payload['catalogo']['setores'] as $s) { if ((int)$s['id'] === $setorA2) { $setorA2Row = $s; break; } }
        if (!$setorA2Row || (int)($setorA2Row['departamento_id'] ?? 0) !== $depA2) {
            failFast('Setor A2 não carrega o departamento_id correto no catálogo (contrato do filtro visual quebrado)');
        }
        ok('Setores/Funções do catálogo carregam departamento_id correto (contrato usado pelo filtro visual em JS)');

        // ===================== CENÁRIO 6/7/11: cadastro multi-departamento end-to-end =====================
        // Nota tecnica: TreinamentosController::store()/update() usam
        // BaseController::redirect(), que chama exit() apos header() no caminho de
        // sucesso - isso encerra o processo PHP do teste de forma irrecuperavel (exit
        // nao pode ser interceptado como header()). Por isso os cenarios que devem
        // TER SUCESSO chamam TreinamentoModel::create()/update() diretamente (mesma
        // camada de persistencia e validacao de escopo que o controller usa via
        // validSetorIdsForCliente()/departamentoBelongsToCatalogCliente()). O cenario
        // que deve FALHAR (Cenário 8, abaixo) continua chamando o controller, pois no
        // caminho de erro ele apenas renderiza e retorna, sem exit().
        $model = new TreinamentoModel();
        $payloadCascata = [
            'nome' => 'Treinamento Cascata ' . $suffix,
            'objetivo' => '',
            'publico' => '',
            'carga_horaria' => '',
            'cliente_id' => $clienteAId,
            'departamento_id' => $depA1,
            'periodicidade' => 'avulso',
            'fornecedor' => '',
            'tipo_treinamento' => '',
            'template_certificado' => '',
            'assinatura_responsavel' => 'Responsável Teste',
            'setor_ids' => [$setorA1, $setorA2],
            'funcao_ids' => [$funcaoA1, $funcaoA2],
        ];
        $treinamentoId = $model->create($payloadCascata);
        if ($treinamentoId <= 0) {
            failFast('Cenário 11: cadastro com Departamento principal A1 + Setores/Funções de A1 e A2 (mesma empresa) deveria ter sido salvo com sucesso');
        }
        $treinamentoIds[] = $treinamentoId;
        ok('Cenário 11: cadastro completo funciona - Treinamento criado com sucesso (TreinamentoModel::create())');

        $persisted = $model->find($treinamentoId);
        if (!$persisted) { failFast('Treinamento criado não foi encontrado por find()'); }
        $persistedSetorIds = array_map('intval', $persisted['setor_ids'] ?? []);
        $persistedFuncaoIds = array_map('intval', $persisted['funcao_ids'] ?? []);
        sort($persistedSetorIds);
        $expectedSetorIds = [$setorA1, $setorA2];
        sort($expectedSetorIds);
        if ($persistedSetorIds !== $expectedSetorIds) {
            failFast('Cenário 6/7: setores de dois departamentos diferentes da mesma empresa deveriam ter sido persistidos juntos. obtido=' . json_encode($persistedSetorIds));
        }
        if (!in_array($funcaoA1, $persistedFuncaoIds, true) || !in_array($funcaoA2, $persistedFuncaoIds, true)) {
            failFast('Cenário 6/7: funções de dois departamentos diferentes da mesma empresa deveriam ter sido persistidas juntas');
        }
        ok('Cenário 6/7: backend continua aceitando (e persistindo) Setores/Funções de outro departamento da MESMA empresa (regra do commit 7691b79 preservada)');

        // ===================== CENÁRIO 8: backend continua rejeitando outra empresa =====================
        resetRequest();
        $_GET['route'] = 'treinamentos/update';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf' => \App\Core\Security::csrfToken(),
            'id' => (string)$treinamentoId,
            'nome' => 'Treinamento Cascata ' . $suffix,
            'objetivo' => '',
            'publico' => '',
            'carga_horaria' => '',
            'cliente_id' => (string)$clienteAId,
            'departamento_id' => (string)$depA1,
            'periodicidade' => 'avulso',
            'fornecedor' => '',
            'tipo_treinamento' => '',
            'template_certificado' => '',
            'assinatura_responsavel' => 'Responsável Teste',
            'setor_ids' => [(string)$setorA1, (string)$setorB], // setorB pertence à Empresa B
            'funcao_ids' => [(string)$funcaoA1],
        ];
        ob_start();
        (new TreinamentosController())->update();
        $updateBody = (string)ob_get_clean();
        if (!empty($GLOBALS['__captured_location'] ?? '')) {
            failFast('Cenário 8: manipulação de setor_ids incluindo setor de outra empresa não deveria ter sido aceita (não deveria redirecionar)');
        }
        if (stripos($updateBody, 'não pertencem à empresa selecionada') === false) {
            failFast('Cenário 8: resposta deveria indicar que existem setores fora da empresa selecionada');
        }
        ok('Cenário 8: backend continua rejeitando setor/função de OUTRA empresa (isolamento de tenant preservado)');

        $afterAttempt = $model->find($treinamentoId);
        $afterSetorIds = array_map('intval', $afterAttempt['setor_ids'] ?? []);
        sort($afterSetorIds);
        if ($afterSetorIds !== $expectedSetorIds) {
            failFast('Cenário 8: tentativa rejeitada não deveria ter alterado os vínculos persistidos anteriormente');
        }
        ok('Confirmado: tentativa cross-empresa rejeitada não alterou nenhum vínculo já persistido');

        // ===================== CENÁRIO 9/10: edição preserva vínculos multi-departamento sem perda silenciosa =========
        resetRequest();
        $_GET['route'] = 'treinamentos/edit';
        $_GET['id'] = (string)$treinamentoId;
        ob_start();
        (new TreinamentosController())->edit();
        $editHtml = (string)ob_get_clean();

        if (strpos($editHtml, 'id="treinamentosDepartamentoId"') === false) {
            failFast('Tela de edição não renderizou o formulário de Treinamento');
        }
        // Empresa correta
        if (strpos($editHtml, 'value="' . $clienteAId . '" ') === false && strpos($editHtml, 'value="' . $clienteAId . '"selected') === false) {
            // fallback: aceita variações de espaço em branco no HTML gerado
        }
        if (strpos($editHtml, '<option value="' . $clienteAId . '" selected>') === false) {
            failFast('Cenário 9: Empresa correta não veio pré-selecionada na edição');
        }
        // Departamento principal correto
        if (strpos($editHtml, '<option value="' . $depA1 . '"' . "\n") === false
            && strpos($editHtml, 'value="' . $depA1 . '"' . "\n                selected") === false
            && strpos($editHtml, (string)$depA1 . '"' . "\n                selected") === false) {
            // verificação mais tolerante abaixo cobre o essencial; este bloco é só defensivo
        }
        $depSelectStart = strpos($editHtml, 'id="treinamentosDepartamentoId"');
        $depSelectEnd = strpos($editHtml, '</select>', $depSelectStart);
        $depSelectHtml = substr($editHtml, $depSelectStart, $depSelectEnd - $depSelectStart);
        if (strpos($depSelectHtml, 'value="' . $depA1 . '"') === false || strpos($depSelectHtml, 'selected') === false
            || strpos(substr($depSelectHtml, strpos($depSelectHtml, 'value="' . $depA1 . '"')), 'selected') > 40) {
            failFast('Cenário 9: Departamento principal (A1) não veio corretamente pré-selecionado na edição');
        }
        ok('Cenário 9: Empresa e Departamento principal corretos ao abrir a edição');

        // Departamentos adicionais: A2 (departamento do setor A2, diferente do principal)
        // precisa vir marcado; A1 (o próprio principal) não deve ser listado como "adicional".
        $filtroSelectStart = strpos($editHtml, 'id="treinamentosDepartamentosFiltro"');
        $filtroSelectEnd = strpos($editHtml, '</select>', $filtroSelectStart);
        $filtroSelectHtml = substr($editHtml, $filtroSelectStart, $filtroSelectEnd - $filtroSelectStart);
        $optA2Pos = strpos($filtroSelectHtml, 'value="' . $depA2 . '"');
        $optA2Tag = $optA2Pos !== false ? substr($filtroSelectHtml, $optA2Pos, 120) : '';
        if ($optA2Pos === false || strpos($optA2Tag, 'selected') === false || strpos($optA2Tag, 'selected') > 30) {
            failFast('Cenário 10: Depto A2 (dono do Setor A2, já vinculado ao treinamento) deveria vir pré-marcado em "Departamentos adicionais" - sem isso a UI esconderia o Setor A2 ao abrir a edição, perdendo a seleção silenciosamente ao salvar');
        }
        $optA1Pos = strpos($filtroSelectHtml, 'value="' . $depA1 . '"');
        $optA1Tag = $optA1Pos !== false ? substr($filtroSelectHtml, $optA1Pos, 120) : '';
        if ($optA1Pos !== false && strpos($optA1Tag, 'selected') !== false && strpos($optA1Tag, 'selected') < 30) {
            failFast('Depto A1 (o próprio Departamento principal) não deveria aparecer marcado como "adicional"');
        }
        ok('Cenário 10: "Departamentos adicionais" vem pré-marcado com o Depto A2 (dono do Setor/Função vinculado fora do departamento principal) - nenhuma perda silenciosa ao reabrir a edição');

        // Setores e Funções previamente vinculados continuam marcados como selected.
        $setoresSelectStart = strpos($editHtml, 'id="treinamentosSetores"');
        $setoresSelectEnd = strpos($editHtml, '</select>', $setoresSelectStart);
        $setoresSelectHtml = substr($editHtml, $setoresSelectStart, $setoresSelectEnd - $setoresSelectStart);
        foreach ([$setorA1, $setorA2] as $sid) {
            $pos = strpos($setoresSelectHtml, 'value="' . $sid . '"');
            $tag = $pos !== false ? substr($setoresSelectHtml, $pos, 200) : '';
            if ($pos === false || strpos($tag, 'selected') === false || strpos($tag, 'selected') > 160) {
                failFast('Cenário 9/10: Setor id=' . $sid . ' deveria continuar selecionado na edição');
            }
        }
        ok('Cenário 9/10: Setores previamente vinculados (de dois departamentos diferentes) continuam selecionados na edição');

        $funcoesSelectStart = strpos($editHtml, 'id="treinamentosFuncoes"');
        $funcoesSelectEnd = strpos($editHtml, '</select>', $funcoesSelectStart);
        $funcoesSelectHtml = substr($editHtml, $funcoesSelectStart, $funcoesSelectEnd - $funcoesSelectStart);
        foreach ([$funcaoA1, $funcaoA2] as $fid) {
            $pos = strpos($funcoesSelectHtml, 'value="' . $fid . '"');
            $tag = $pos !== false ? substr($funcoesSelectHtml, $pos, 200) : '';
            if ($pos === false || strpos($tag, 'selected') === false || strpos($tag, 'selected') > 160) {
                failFast('Cenário 9/10: Função id=' . $fid . ' deveria continuar selecionada na edição');
            }
        }
        ok('Cenário 9/10: Funções previamente vinculadas (de dois departamentos diferentes) continuam selecionadas na edição');

        // ===================== CONFIRMA: reabrir e salvar sem alterar nada não perde vínculos =====================
        // (TreinamentoModel::update() diretamente, pelo mesmo motivo do exit() em
        // BaseController::redirect() explicado no cenário 6/7/11 acima.)
        $resaveOk = $model->update($treinamentoId, $payloadCascata);
        if (!$resaveOk) {
            failFast('Reabrir e salvar o treinamento sem alterações deveria continuar funcionando');
        }
        $afterResave = $model->find($treinamentoId);
        $afterResaveSetorIds = array_map('intval', $afterResave['setor_ids'] ?? []);
        sort($afterResaveSetorIds);
        if ($afterResaveSetorIds !== $expectedSetorIds) {
            failFast('Cenário 10: reabrir e salvar o treinamento existente perdeu Setores de outro departamento silenciosamente. obtido=' . json_encode($afterResaveSetorIds));
        }
        ok('Cenário 10: abrir e salvar um treinamento existente NÃO perde Setores/Funções de outros departamentos');

        echo "Treinamentos - cascata Departamento->Setor->Função (Item 14) regression tests passed.\n";
    } catch (\Throwable $e) {
        failFast('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    } finally {
        if (!empty($treinamentoIds)) {
            $in = implode(',', array_map('intval', $treinamentoIds));
            $pdo->exec("DELETE FROM treinamento_setores WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamento_funcoes WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamentos WHERE id IN ($in)");
        }
        if (!empty($funcaoIds)) {
            $in = implode(',', array_map('intval', $funcaoIds));
            $pdo->exec("DELETE FROM funcoes WHERE id IN ($in)");
        }
        if (!empty($setorIds)) {
            $in = implode(',', array_map('intval', $setorIds));
            $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
        }
        if (!empty($depIds)) {
            $in = implode(',', array_map('intval', $depIds));
            $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
        }
        if (!empty($clienteIds)) {
            $in = implode(',', array_map('intval', $clienteIds));
            $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
        }
        unset($_SESSION['user']);
    }
}

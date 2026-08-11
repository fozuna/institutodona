<?php
namespace App\Controllers {
    // Intercepta header() apenas dentro do namespace do controller (resolucao de funcao
    // por namespace do PHP), para capturar o "Location:" real emitido por
    // TarefasController::store() sem depender de headers_list() (nao confiavel em CLI).
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require __DIR__ . '/../autoload.php';

    use App\Controllers\TarefasController;
    use App\Core\Security;
    use App\Database\Database;
    use App\Models\ClienteModel;
    use App\Models\TarefaModel;

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

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $clientes = new ClienteModel();
    $clienteAId = $clientes->create(['nome_empresa' => 'Cliente Perfil A ' . $suffix, 'CNPJ' => '11.222.3' . substr($suffix, 0, 2) . '/0001-11', 'contato' => 'Contato A']);
    $clienteBId = $clientes->create(['nome_empresa' => 'Cliente Perfil B ' . $suffix, 'CNPJ' => '22.333.4' . substr($suffix, 0, 2) . '/0001-22', 'contato' => 'Contato B']);
    if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar clientes de teste'); }
    ok('Criou clientes A e B para o teste');

    // ===================== NAVEGACAO =====================

    // 1) Perfil do Cliente -> Criar tarefa (com contexto de cliente): formulario oficial de
    //    Tarefas carrega, cliente vem pre-selecionado, e marca a origem para o retorno pos-save.
    resetRequest();
    $_GET['route'] = 'tarefas/create';
    $_GET['cliente'] = (string)$clienteAId;
    ob_start();
    (new TarefasController())->create();
    $html = (string)ob_get_clean();
    if (strpos($html, 'name="cliente_id"') === false) { failFast('Formulario oficial de Tarefas nao foi renderizado'); }
    if (strpos($html, 'value="' . $clienteAId . '" selected') === false) { failFast('Cliente do Perfil nao veio pre-selecionado no formulario'); }
    if (strpos($html, 'name="voltar_perfil" value="1"') === false) { failFast('Marcador de origem (voltar_perfil) nao foi incluido no formulario'); }
    if (strpos($html, 'route=clientes/show&id=' . $clienteAId) === false) { failFast('Link "Voltar" nao aponta para o Perfil do Cliente de origem'); }
    ok('Perfil do Cliente -> Criar tarefa abre o formulario oficial de Tarefas com o cliente pre-selecionado');

    // 2) Acesso tradicional via modulo Tarefas (sem contexto de cliente): comportamento inalterado.
    resetRequest();
    $_GET['route'] = 'tarefas/create';
    ob_start();
    (new TarefasController())->create();
    $htmlSemContexto = (string)ob_get_clean();
    if (strpos($htmlSemContexto, 'name="voltar_perfil"') !== false) { failFast('Regressao: acesso tradicional a tarefas/create nao deveria incluir voltar_perfil'); }
    if (strpos($htmlSemContexto, 'route=tarefas/index') === false) { failFast('Regressao: link "Voltar" tradicional deveria apontar para tarefas/index'); }
    ok('Regressão: acesso tradicional a tarefas/create (sem contexto) continua sem pré-seleção nem marcador de origem');

    // 3) Cliente inexistente/invalido na querystring e ignorado com seguranca (nao quebra, nao seleciona nada).
    resetRequest();
    $_GET['route'] = 'tarefas/create';
    $_GET['cliente'] = '999999999';
    ob_start();
    (new TarefasController())->create();
    $htmlInvalido = (string)ob_get_clean();
    if (strpos($htmlInvalido, 'name="voltar_perfil"') !== false) { failFast('Cliente inexistente nao deveria marcar origem de Perfil do Cliente'); }
    ok('Cliente inexistente/inacessível na querystring é ignorado com segurança (sem selecionar nada)');

    // ===================== PERSISTENCIA =====================

    $metodologiasCountBefore = (int)$pdo->query("SELECT COUNT(*) FROM metodologias WHERE item_pilar LIKE 'Tarefa via Perfil%'")->fetchColumn();

    resetRequest();
    $_GET['route'] = 'tarefas/store';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf' => Security::csrfToken(),
        'cliente_id' => (string)$clienteAId,
        'titulo' => 'Tarefa via Perfil ' . $suffix,
        'descricao' => 'Criada a partir do Perfil do Cliente',
        'data_inicio' => '2026-09-01T09:00',
        'data_fim' => '',
        'prioridade' => 'alta',
        'status' => 'Planejado',
        'voltar_perfil' => '1',
    ];
    ob_start();
    (new TarefasController())->store();
    ob_end_clean();

    $location = $GLOBALS['__captured_location'] ?? '';
    if ($location !== 'index.php?route=clientes/show&id=' . $clienteAId) {
        failFast('Pós-cadastro via Perfil do Cliente não retornou para clientes/show (obtido: ' . $location . ')');
    }
    ok('Após salvar via Perfil do Cliente, retorna para clientes/show&id=' . $clienteAId . ' (não para Metodologias/Kanban)');

    $tarefas = new TarefaModel();
    $criadas = array_values(array_filter($tarefas->all($clienteAId), static fn(array $t): bool => $t['titulo'] === 'Tarefa via Perfil ' . $suffix));
    if (count($criadas) !== 1) { failFast('Tarefa não foi persistida na tabela tarefas (ou foi persistida mais de uma vez)'); }
    $tarefaCriada = $criadas[0];
    ok('Tarefa foi persistida na tabela oficial "tarefas"');
    if ($tarefaCriada['status'] !== 'Planejado' || $tarefaCriada['prioridade'] !== 'alta') {
        failFast('Status/prioridade não foram persistidos corretamente');
    }
    ok('Status e prioridade persistidos corretamente');

    $metodologiasCountAfter = (int)$pdo->query("SELECT COUNT(*) FROM metodologias WHERE item_pilar LIKE 'Tarefa via Perfil%'")->fetchColumn();
    if ($metodologiasCountAfter !== $metodologiasCountBefore) {
        failFast('Regressão: um registro indevido foi criado em "metodologias"');
    }
    ok('Nenhum registro indevido foi criado em "metodologias"');

    // A tarefa aparece na listagem oficial, filtrando pelo cliente correspondente.
    resetRequest();
    $_GET['route'] = 'tarefas/index';
    $_GET['cliente'] = (string)$clienteAId;
    ob_start();
    (new TarefasController())->index();
    $indexHtml = (string)ob_get_clean();
    if (strpos($indexHtml, 'Tarefa via Perfil ' . $suffix) === false) {
        failFast('Tarefa criada não aparece na listagem oficial filtrada pelo cliente');
    }
    ok('Tarefa aparece na listagem oficial de Tarefas, filtrada pelo cliente correspondente');

    // ===================== REGRESSAO: fluxo tradicional do modulo Tarefas =====================

    resetRequest();
    $_GET['route'] = 'tarefas/store';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf' => Security::csrfToken(),
        'cliente_id' => (string)$clienteAId,
        'titulo' => 'Tarefa Tradicional ' . $suffix,
        'descricao' => '',
        'data_inicio' => '2026-09-02T09:00',
        'data_fim' => '',
        'prioridade' => 'media',
        'status' => 'Planejado',
        // sem voltar_perfil: fluxo tradicional pelo módulo Tarefas.
    ];
    ob_start();
    (new TarefasController())->store();
    ob_end_clean();
    $locationTradicional = $GLOBALS['__captured_location'] ?? '';
    if ($locationTradicional !== 'index.php?route=tarefas/index&cliente=' . $clienteAId) {
        failFast('Regressão: fluxo tradicional de criação de tarefa não redireciona mais para tarefas/index (obtido: ' . $locationTradicional . ')');
    }
    ok('Regressão: criação tradicional pelo módulo Tarefas continua redirecionando para tarefas/index');

    // Edição continua funcionando.
    $tradicional = array_values(array_filter($tarefas->all($clienteAId), static fn(array $t): bool => $t['titulo'] === 'Tarefa Tradicional ' . $suffix))[0] ?? null;
    if (!$tradicional) { failFast('Tarefa tradicional não foi persistida'); }
    resetRequest();
    $_GET['route'] = 'tarefas/edit';
    $_GET['id'] = (string)$tradicional['id'];
    ob_start();
    (new TarefasController())->edit();
    $editHtml = (string)ob_get_clean();
    if (strpos($editHtml, 'name="titulo"') === false) { failFast('Regressão: tela de edição de tarefa parou de renderizar o formulário'); }
    ok('Regressão: edição de tarefa continua funcionando');

    // Filtro "Todos" (sem cliente) continua funcionando e inclui as tarefas criadas.
    resetRequest();
    $_GET['route'] = 'tarefas/index';
    ob_start();
    (new TarefasController())->index();
    $indexTodos = (string)ob_get_clean();
    if (strpos($indexTodos, 'Tarefa via Perfil ' . $suffix) === false || strpos($indexTodos, 'Tarefa Tradicional ' . $suffix) === false) {
        failFast('Regressão: listagem sem filtro de cliente parou de exibir as tarefas');
    }
    ok('Regressão: filtro "Todos" da listagem de Tarefas continua funcionando');

    // ===================== TENANT / RBAC (camada de modelo) =====================

    // Cliente Admin restrito à empresa B: tentativa de criar tarefa para a empresa A (manipulando
    // cliente_id) é neutralizada pelo próprio TarefaModel::create() (normalizeScopedClienteId +
    // canAccessClienteId), sem nunca gravar a tarefa na empresa não autorizada.
    $_SESSION['user'] = [
        'id' => 2,
        'nome' => 'Cliente Admin B',
        'email' => 'admin.b@example.com',
        'tipo_acesso' => 'cliente_admin',
        'allowed_client_ids' => [$clienteBId],
    ];
    $tarefasComoAdminB = new TarefaModel();
    $idManipulado = $tarefasComoAdminB->create([
        'cliente_id' => $clienteAId, // empresa fora do tenant do Cliente Admin B
        'titulo' => 'Tentativa Cross-Tenant ' . $suffix,
        'data_inicio' => '2026-09-03T09:00',
        'prioridade' => 'media',
        'status' => 'Planejado',
    ]);
    if ($idManipulado <= 0) { failFast('Cliente Admin não conseguiu criar tarefa nem mesmo dentro do próprio tenant'); }
    $tarefaManipulada = $tarefasComoAdminB->find($idManipulado);
    if ((int)($tarefaManipulada['cliente_id'] ?? 0) !== $clienteBId) {
        failFast('Falha de isolamento: tarefa foi gravada para a empresa A mesmo sob sessão restrita à empresa B');
    }
    ok('Tentativa de manipular cliente_id para empresa fora do tenant é neutralizada; tarefa é gravada apenas na própria empresa do Cliente Admin');

    // Cliente Admin criando normalmente para a própria empresa: funciona.
    $idProprio = $tarefasComoAdminB->create([
        'cliente_id' => $clienteBId,
        'titulo' => 'Tarefa Própria Admin B ' . $suffix,
        'data_inicio' => '2026-09-04T09:00',
        'prioridade' => 'baixa',
        'status' => 'Planejado',
    ]);
    if ($idProprio <= 0) { failFast('Cliente Admin não conseguiu criar tarefa para a própria empresa'); }
    ok('Cliente Admin cria normalmente uma tarefa para a própria empresa');

    // Instituto preserva o escopo normal: consegue criar para qualquer uma das duas empresas.
    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];
    $tarefasComoInstituto = new TarefaModel();
    $idInstitutoA = $tarefasComoInstituto->create([
        'cliente_id' => $clienteAId,
        'titulo' => 'Tarefa Instituto A ' . $suffix,
        'data_inicio' => '2026-09-05T09:00',
        'prioridade' => 'media',
        'status' => 'Planejado',
    ]);
    $idInstitutoB = $tarefasComoInstituto->create([
        'cliente_id' => $clienteBId,
        'titulo' => 'Tarefa Instituto B ' . $suffix,
        'data_inicio' => '2026-09-05T10:00',
        'prioridade' => 'media',
        'status' => 'Planejado',
    ]);
    if ($idInstitutoA <= 0 || $idInstitutoB <= 0) { failFast('Instituto perdeu a capacidade de criar tarefas para qualquer empresa'); }
    ok('Instituto preserva o escopo normal: cria tarefas para qualquer empresa');

    echo "Tarefas via Perfil do Cliente regression tests passed.\n";
}

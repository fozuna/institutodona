<?php
namespace App\Controllers {
    // Intercepta header() apenas dentro do namespace do controller (resolucao de funcao
    // por namespace do PHP), para capturar o "Location:" real emitido por
    // PlanoAcaoController::delete() sem depender de headers_list() (nao confiavel em CLI).
    // Mesmo mecanismo usado em tarefas_criar_via_perfil_cliente_regression_test.php.
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require __DIR__ . '/../autoload.php';

    use App\Controllers\PlanoAcaoController;
    use App\Core\Security;
    use App\Database\Database;
    use App\Models\ClienteModel;
    use App\Models\PlanoAcaoTaskModel;

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
    $usuarioIds = [];
    $taskIds = [];

    try {
        // ===================== FIXTURES =====================
        $clientes = new ClienteModel();
        $clienteAId = $clientes->create(['nome_empresa' => 'Cliente PA Perfil A ' . $suffix, 'CNPJ' => '10.100.1' . substr($suffix, 0, 2) . '/0001-01', 'contato' => 'Contato A']);
        $clienteBId = $clientes->create(['nome_empresa' => 'Cliente PA Perfil B ' . $suffix, 'CNPJ' => '20.200.2' . substr($suffix, 0, 2) . '/0001-02', 'contato' => 'Contato B']);
        if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar clientes de teste'); }
        $clienteIds[] = $clienteAId;
        $clienteIds[] = $clienteBId;
        ok('Criou clientes A e B para o teste');

        $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:n, :e, :h, 'instituto', NULL)")
            ->execute(['n' => 'Instituto PA Perfil ' . $suffix, 'e' => 'instituto.paperfil.' . $suffix . '@test.local', 'h' => password_hash('x', PASSWORD_DEFAULT)]);
        $institutoUserId = (int)$pdo->lastInsertId();
        $usuarioIds[] = $institutoUserId;

        $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:n, :e, :h, 'cliente_admin', :cid)")
            ->execute(['n' => 'Cliente Admin PA Perfil ' . $suffix, 'e' => 'admin.paperfil.' . $suffix . '@test.local', 'h' => password_hash('x', PASSWORD_DEFAULT), 'cid' => $clienteAId]);
        $clienteAdminAUserId = (int)$pdo->lastInsertId();
        $usuarioIds[] = $clienteAdminAUserId;
        ok('Criou usuários reais de teste (Instituto e Cliente Admin vinculado ao Cliente A)');

        $sessionInstituto = static function () use ($institutoUserId): array {
            return [
                'id' => $institutoUserId,
                'nome' => 'Instituto PA Perfil',
                'email' => 'instituto.paperfil@test.local',
                'tipo_acesso' => 'instituto',
                'id_cliente' => null,
                'allowed_client_ids' => [],
            ];
        };
        $sessionClienteAdminA = static function () use ($clienteAdminAUserId, $clienteAId): array {
            return [
                'id' => $clienteAdminAUserId,
                'nome' => 'Cliente Admin PA Perfil',
                'email' => 'admin.paperfil@test.local',
                'tipo_acesso' => 'cliente_admin',
                'id_cliente' => $clienteAId,
                'allowed_client_ids' => [$clienteAId],
            ];
        };

        $_SESSION['user'] = $sessionInstituto();
        $tasks = new PlanoAcaoTaskModel();

        // Planos de Ação de apoio (criados como Instituto, que enxerga qualquer cliente).
        $taskA1 = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => 'PA Perfil Alvo ' . $suffix, 'status' => 'Planejado']);
        $taskA1Irmao = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => 'PA Perfil Irmão ' . $suffix, 'status' => 'Planejado']);
        $taskA2 = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => 'PA Módulo ' . $suffix, 'status' => 'Planejado']);
        $taskA3 = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => 'PA Cliente Admin ' . $suffix, 'status' => 'Planejado']);
        $taskB1 = $tasks->create(['id_cliente' => $clienteBId, 'titulo' => 'PA Cross-Tenant ' . $suffix, 'status' => 'Planejado']);
        $taskA4 = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => 'PA CSRF Inválido ' . $suffix, 'status' => 'Planejado']);
        foreach ([$taskA1, $taskA1Irmao, $taskA2, $taskA3, $taskB1, $taskA4] as $tid) {
            if ($tid <= 0) { failFast('Falha ao criar Plano de Ação de apoio para o teste'); }
            $taskIds[] = $tid;
        }
        ok('Criou os Planos de Ação de apoio para os cenários');

        // ===================== CENÁRIO 1 (+ 7): Instituto exclui pelo Perfil do Cliente =====================
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => (string)$taskA1, 'csrf' => Security::csrfToken(), 'voltar_perfil' => '1'];
        ob_start();
        (new PlanoAcaoController())->delete();
        ob_end_clean();
        $location = $GLOBALS['__captured_location'] ?? '';
        if ($location !== 'index.php?route=clientes/show&id=' . $clienteAId) {
            failFast('Cenário 1: exclusão via Perfil do Cliente não retornou para clientes/show (obtido: ' . $location . ')');
        }
        ok('Cenário 1: Instituto exclui plano pelo Perfil do Cliente e retorna ao Perfil correto (clientes/show&id=' . $clienteAId . ')');

        if ($tasks->find($taskA1) !== null) { failFast('Cenário 1: Plano de Ação alvo deveria ter sido excluído'); }
        if ($tasks->find($taskA1Irmao) === null) { failFast('Cenário 7: exclusão removeu o registro irmão além do alvo'); }
        ok('Cenário 7: apenas o Plano de Ação alvo foi removido; o registro irmão do mesmo cliente permanece intacto');

        // ===================== CENÁRIO 2: exclusão sem marcador (contexto do módulo) =====================
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => (string)$taskA2, 'csrf' => Security::csrfToken()]; // sem voltar_perfil
        ob_start();
        (new PlanoAcaoController())->delete();
        ob_end_clean();
        $location = $GLOBALS['__captured_location'] ?? '';
        if ($location !== 'index.php?route=planoacao/index&cliente=' . $clienteAId) {
            failFast('Cenário 2: exclusão sem voltar_perfil deveria manter o retorno atual ao módulo (obtido: ' . $location . ')');
        }
        if ($tasks->find($taskA2) !== null) { failFast('Cenário 2: Plano de Ação deveria ter sido excluído normalmente'); }
        ok('Cenário 2: exclusão sem marcador de origem mantém o comportamento atual (planoacao/index&cliente=X)');

        // ===================== CENÁRIO 3: Cliente Admin exclui plano da própria empresa pelo Perfil =====================
        $_SESSION['user'] = $sessionClienteAdminA();
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => (string)$taskA3, 'csrf' => Security::csrfToken(), 'voltar_perfil' => '1'];
        ob_start();
        (new PlanoAcaoController())->delete();
        ob_end_clean();
        $location = $GLOBALS['__captured_location'] ?? '';
        if ($location !== 'index.php?route=clientes/show&id=' . $clienteAId) {
            failFast('Cenário 3: Cliente Admin (própria empresa) deveria retornar ao Perfil correto (obtido: ' . $location . ')');
        }
        ok('Cenário 3: Cliente Admin exclui plano da própria empresa pelo Perfil e retorna ao Perfil correto');

        // ===================== CENÁRIO 4: Cliente Admin tenta excluir plano de outro tenant (manipulação de id) =====================
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => (string)$taskB1, 'csrf' => Security::csrfToken(), 'voltar_perfil' => '1'];
        ob_start();
        (new PlanoAcaoController())->delete();
        ob_end_clean();
        $location = $GLOBALS['__captured_location'] ?? '';
        if ($location !== 'index.php?route=planoacao/index') {
            failFast('Cenário 4: manipulação de id cross-tenant deveria cair no ramo "não encontrado" sem cliente no redirect (obtido: ' . $location . ')');
        }
        ok('Cenário 4: Cliente Admin não localiza (find() escopado por tenant) o Plano de Ação do Cliente B; nenhuma tentativa de exclusão prossegue');

        // Confirma, sob a visão irrestrita do Instituto, que o registro do Cliente B
        // continua fisicamente intacto (find() do Cliente Admin não é prova suficiente,
        // pois também retornaria null se o registro simplesmente estivesse fora de escopo).
        $_SESSION['user'] = $sessionInstituto();
        if ($tasks->find($taskB1) === null) {
            failFast('Falha de segurança: Plano de Ação do Cliente B foi excluído por manipulação de id vindo de sessão de outro tenant');
        }
        ok('Confirmado (visão do Instituto): o Plano de Ação do Cliente B permanece intacto após a tentativa cross-tenant');

        // ===================== CENÁRIO 5: Plano inexistente =====================
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => '999999999', 'csrf' => Security::csrfToken(), 'voltar_perfil' => '1'];
        $erroDurante = null;
        ob_start();
        try {
            (new PlanoAcaoController())->delete();
        } catch (\Throwable $e) {
            $erroDurante = $e;
        }
        ob_end_clean();
        if ($erroDurante !== null) {
            failFast('Cenário 5: exclusão de id inexistente lançou exceção: ' . $erroDurante->getMessage());
        }
        $location = $GLOBALS['__captured_location'] ?? '';
        if ($location !== 'index.php?route=planoacao/index') {
            failFast('Cenário 5: tratamento inesperado para Plano de Ação inexistente (obtido: ' . $location . ')');
        }
        ok('Cenário 5: Plano de Ação inexistente é tratado com segurança (sem exceção, redirect neutro)');

        // ===================== CENÁRIO 6: CSRF inválido =====================
        resetRequest();
        $_GET['route'] = 'planoacao/delete';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['id' => (string)$taskA4, 'csrf' => 'token-invalido-' . $suffix, 'voltar_perfil' => '1'];
        ob_start();
        (new PlanoAcaoController())->delete();
        $body = (string)ob_get_clean();
        if (stripos($body, 'CSRF') === false) {
            failFast('Cenário 6: resposta deveria indicar CSRF inválido (obtido: ' . $body . ')');
        }
        if (!empty($GLOBALS['__captured_location'] ?? '')) {
            failFast('Cenário 6: CSRF inválido não deveria produzir nenhum redirect');
        }
        ok('Cenário 6: CSRF inválido bloqueia a exclusão antes de tocar no registro');

        if ($tasks->find($taskA4) === null) {
            failFast('Cenário 6: Plano de Ação não deveria ter sido excluído com CSRF inválido');
        }
        ok('Confirmado: nenhuma exclusão ocorre quando o CSRF é inválido');

        echo "Planos de Ação — retorno pós-exclusão via Perfil do Cliente (Item 03) regression tests passed.\n";
    } catch (\Throwable $e) {
        failFast('Exceção: ' . $e->getMessage());
    } finally {
        if (!empty($taskIds)) {
            $in = implode(',', array_map('intval', $taskIds));
            $pdo->exec("DELETE FROM planoacao_history WHERE item_type = 'task' AND item_id IN ($in)");
            $pdo->exec("DELETE FROM pdca_tasks WHERE id IN ($in)");
        }
        if (!empty($usuarioIds)) {
            $in = implode(',', array_map('intval', $usuarioIds));
            $pdo->exec("DELETE FROM usuarios WHERE id IN ($in)");
        }
        if (!empty($clienteIds)) {
            $in = implode(',', array_map('intval', $clienteIds));
            $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
        }
        unset($_SESSION['user']);
    }
}

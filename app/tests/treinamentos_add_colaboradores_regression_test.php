<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\TreinamentosController;
use App\Core\Security;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\TreinamentoModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$suffix = 'addcol_' . substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'colaborador_ids' => [], 'setor_ids' => [], 'funcao_ids' => [], 'departamento_ids' => [],
    'treinamento_id' => 0, 'cliente_ids' => [],
];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['treinamento_id'])) {
            $pdo->prepare('DELETE FROM treinamento_colaboradores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_setores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_funcoes WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $cleanup['treinamento_id']]);
        }
        foreach ($cleanup['colaborador_ids'] as $id) { $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['funcao_ids'] as $id) { $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$funcoes = new FuncaoModel();
$model = new TreinamentoModel();

$clienteId = $clientes->create(['nome_empresa' => 'Cliente AddColab ' . $suffix, 'CNPJ' => '55.222.1' . substr($suffix, 0, 2) . '/0001-55', 'contato' => 'Teste']);
$outroClienteId = $clientes->create(['nome_empresa' => 'Cliente AddColab Outro ' . $suffix, 'CNPJ' => '55.222.2' . substr($suffix, 0, 2) . '/0001-55', 'contato' => 'Teste']);
if ($clienteId <= 0 || $outroClienteId <= 0) failFast('Falha ao criar clientes de teste');
$cleanup['cliente_ids'] = [$clienteId, $outroClienteId];

$depA = $departamentos->create(['nome' => 'Dep AddColab ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorA = $setores->create(['nome' => 'Setor AddColab ' . $suffix, 'departamento_id' => $depA]);
$funcaoA = $funcoes->create(['nome' => 'Funcao AddColab ' . $suffix, 'setor_id' => $setorA]);
if (!$depA || !$setorA || !$funcaoA) failFast('Falha ao criar estrutura de departamento/setor/função de teste');
$cleanup['departamento_ids'] = [$depA];
$cleanup['setor_ids'] = [$setorA];
$cleanup['funcao_ids'] = [$funcaoA];

$stmt = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n, :e, :f, :c)');
$stmt->execute(['n' => 'Colaborador A1 ' . $suffix, 'e' => 'colabA1.' . $suffix . '@test.local', 'f' => $funcaoA, 'c' => $clienteId]);
$colaboradorA1 = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador A2 ' . $suffix, 'e' => 'colabA2.' . $suffix . '@test.local', 'f' => $funcaoA, 'c' => $clienteId]);
$colaboradorA2 = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador Outro ' . $suffix, 'e' => 'colabC.' . $suffix . '@test.local', 'f' => $funcaoA, 'c' => $outroClienteId]);
$colaboradorOutro = (int)$pdo->lastInsertId();
$cleanup['colaborador_ids'] = [$colaboradorA1, $colaboradorA2, $colaboradorOutro];
ok('Criou colaboradores A1, A2 (empresa do treinamento) e Outro (outra empresa)');

$treinamentoId = $model->create([
    'nome' => 'Treinamento AddColab ' . $suffix,
    'objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '4',
    'cliente_id' => $clienteId, 'departamento_id' => $depA, 'periodicidade' => 'anual', 'fornecedor' => 'Fornecedor',
    'setor_ids' => [$setorA], 'funcao_ids' => [$funcaoA],
]);
if ($treinamentoId <= 0) failFast('Falha ao criar treinamento de teste');
$cleanup['treinamento_id'] = $treinamentoId;
ok('Criou treinamento de teste');

// addColaboradores() sempre termina em redirect() (header + exit), então é preciso rodar em
// subprocesso (mesmo padrão já usado para addParticipantes() em treinamentos_participante_extra_regression_test.php).
// Um shutdown function no probe devolve o flash_success/flash_error via stdout.
function postAddColaboradores(int $treinamentoId, array $colaboradorIds): array
{
    $probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'treinamentos_add_colaboradores_probe.php';
    $args = array_merge([(string)$treinamentoId], array_map('strval', $colaboradorIds));
    $cmd = 'php ' . escapeshellarg($probe);
    foreach ($args as $arg) {
        $cmd .= ' ' . escapeshellarg($arg);
    }
    $output = (string)@exec($cmd . ' 2>&1', $lines);
    foreach ($lines as $line) {
        if (str_starts_with($line, 'PROBE_RESULT:')) {
            $decoded = json_decode(substr($line, strlen('PROBE_RESULT:')), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }
    failFast('Subprocesso addColaboradores não retornou resultado esperado. Saída: ' . implode("\n", $lines));
    return [];
}

// 1) Selecionar um colaborador e salvar com sucesso.
$result = postAddColaboradores($treinamentoId, [$colaboradorA1]);
if (($result['flash_error'] ?? null) !== null) failFast('Seleção única não deveria gerar flash_error: ' . $result['flash_error']);
$linked = $model->linkedColaboradores($treinamentoId);
if (count($linked) !== 1 || (int)$linked[0]['colaborador_id'] !== $colaboradorA1) {
    failFast('Deveria haver exatamente 1 vínculo pendente (colaborador A1) após a primeira seleção');
}
ok('Seleciona um colaborador e salva com sucesso (sem 500, sem flash_error)');

// 2) Selecionar múltiplos colaboradores (A1 + A2): ambos devem ficar vinculados, sem duplicar A1.
$result = postAddColaboradores($treinamentoId, [$colaboradorA1, $colaboradorA2]);
if (($result['flash_error'] ?? null) !== null) failFast('Seleção múltipla não deveria gerar flash_error: ' . $result['flash_error']);
$linked = $model->linkedColaboradores($treinamentoId);
$linkedIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $linked);
sort($linkedIds);
if ($linkedIds !== [$colaboradorA1, $colaboradorA2]) {
    failFast('Deveria haver exatamente os vínculos A1 e A2, sem duplicidade, após reenviar a seleção');
}
ok('Reenviar a seleção com o mesmo colaborador + um novo não duplica e mantém ambos vinculados');

// 3) Seleção vazia: NÃO deve apagar o pré-cadastro existente, deve mostrar mensagem de validação.
$result = postAddColaboradores($treinamentoId, []);
if (($result['flash_error'] ?? '') !== 'Selecione ao menos um colaborador para continuar.') {
    failFast('Seleção vazia deveria exibir a mensagem de validação de seleção obrigatória, obteve: ' . ($result['flash_error'] ?? '(nenhuma)'));
}
$linked = $model->linkedColaboradores($treinamentoId);
if (count($linked) !== 2) {
    failFast('Regressão crítica: submeter sem seleção apagou o pré-cadastro existente (deveria permanecer com 2 vínculos)');
}
ok('Seleção vazia não gera 500 nem apaga o pré-cadastro existente; exibe mensagem de validação');

// 4) Isolamento de tenant: colaborador de outra empresa deve ser rejeitado, sem vincular ninguém.
$result = postAddColaboradores($treinamentoId, [$colaboradorA1, $colaboradorOutro]);
if (($result['flash_error'] ?? '') !== 'Existem colaboradores inválidos para a unidade do treinamento.') {
    failFast('Colaborador de outra empresa deveria ser rejeitado com mensagem clara, obteve: ' . ($result['flash_error'] ?? '(nenhuma)'));
}
$linked = $model->linkedColaboradores($treinamentoId);
$linkedIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $linked);
if (in_array($colaboradorOutro, $linkedIds, true)) {
    failFast('Colaborador de outra empresa não deveria ter sido vinculado');
}
if (count($linked) !== 2) {
    failFast('Rejeição de colaborador de outra empresa não deveria alterar o pré-cadastro existente');
}
ok('Rejeita colaborador de outra empresa/tenant, sem alterar o pré-cadastro existente');

// 5) Regressão do bug de produção: coluna `origem` ausente em treinamento_colaboradores
//    (schema divergente) não deve mais causar HTTP 500 - TreinamentoSchema::ensure() precisa
//    se auto-curar antes da consulta que referencia a coluna explicitamente.
$pdo->exec('ALTER TABLE treinamento_colaboradores DROP COLUMN origem');
$colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'treinamento_colaboradores' AND column_name = 'origem'");
$colCheck->execute();
if ((int)$colCheck->fetchColumn() !== 0) failFast('Falha ao simular ausência da coluna origem para o teste de regressão');

$result = postAddColaboradores($treinamentoId, [$colaboradorA1, $colaboradorA2]);
if (($result['flash_error'] ?? null) !== null) {
    failFast('Com a coluna origem ausente, addColaboradores deveria se auto-curar via TreinamentoSchema::ensure() e não gerar flash_error: ' . $result['flash_error']);
}
$colCheck->execute();
if ((int)$colCheck->fetchColumn() !== 1) {
    failFast('TreinamentoSchema::ensure() deveria ter recriado a coluna origem automaticamente');
}
$linked = $model->linkedColaboradores($treinamentoId);
if (count($linked) !== 2) {
    failFast('Após a auto-cura do schema, o pré-cadastro deveria continuar com os 2 vínculos (A1 + A2)');
}
ok('Simula o bug de produção (coluna origem ausente): TreinamentoSchema::ensure() se auto-cura e addColaboradores NÃO retorna 500');

// 6) Método/CSRF continuam validados (sem viés de 500): GET não grava, CSRF ausente é rejeitado com 400.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = [];
ob_start();
http_response_code(200);
(new TreinamentosController())->addColaboradores();
$getBody = (string)ob_get_clean();
if (http_response_code() !== 400) failFast('Requisição GET para add_colaboradores deveria ser rejeitada com 400, não gravar');
ok('Requisição GET é rejeitada (400), não executa gravação');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => 'token-invalido', 'treinamento_id' => (string)$treinamentoId, 'colaborador_ids' => [(string)$colaboradorA1]];
ob_start();
http_response_code(200);
(new TreinamentosController())->addColaboradores();
ob_end_clean();
if (http_response_code() !== 400) failFast('CSRF inválido deveria ser rejeitado com 400');
ok('CSRF ausente/inválido é rejeitado com 400');

echo "Treinamentos add_colaboradores regression tests passed.\n";
ob_end_flush();

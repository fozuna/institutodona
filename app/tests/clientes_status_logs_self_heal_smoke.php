<?php
// Correção 01 (inativação de clientes): ClienteModel::ensureColumns() já
// autorreparava as colunas opcionais de `clientes` (inclusive `ativo`) quando
// a migration 20260728120000_clientes_status_ativo_apply não tinha rodado,
// mas não recriava a tabela `cliente_status_logs` - exigida sem fallback por
// updateWithStatusAudit(). Nesse cenário a inativação falhava de forma
// permanente e universal ("Tabela de auditoria de status... não encontrada"),
// mesmo a coluna `ativo` já existindo. Este teste simula o ambiente com a
// migration ausente e confirma que o auto-reparo agora recria a tabela e a
// inativação volta a funcionar. Usa RuntimeException (não exit()) dentro do
// bloco protegido para garantir que o `finally` restaure a tabela original
// mesmo se alguma asserção falhar no meio do teste.
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;

function ok(string $m): void { echo "OK: $m\n"; }

$pdo = Database::getConnection();
if (!Database::tableExists('cliente_status_logs')) {
    echo "FAIL: Pré-condição: cliente_status_logs deveria existir antes do teste.\n";
    exit(1);
}

$backupName = 'cliente_status_logs_backup_selfheal_' . date('YmdHis');
$clienteId = null;
// DDL exata (preserva a FK) para poder restaurar a tabela original depois -
// RENAME TABLE manteria o nome da constraint presa à tabela renomeada
// (colidindo com o CREATE do auto-reparo) e "CREATE TABLE ... LIKE" não
// copia foreign keys no MySQL, então nenhum dos dois é seguro para restaurar.
$originalDdl = $pdo->query('SHOW CREATE TABLE cliente_status_logs')->fetch(PDO::FETCH_ASSOC)['Create Table'];
try {
    // Backup dos dados (estrutura exata não importa aqui, é só um "cofre"
    // temporário) e DROP de verdade da tabela original, para reproduzir
    // fielmente o cenário "migration nunca rodou".
    $pdo->exec("CREATE TABLE `{$backupName}` LIKE cliente_status_logs");
    $pdo->exec("INSERT INTO `{$backupName}` SELECT * FROM cliente_status_logs");
    $pdo->exec('DROP TABLE cliente_status_logs');
    if (Database::tableExists('cliente_status_logs')) {
        throw new RuntimeException('Falha ao simular ausência da tabela cliente_status_logs.');
    }
    ok('Ambiente simulado sem a tabela cliente_status_logs (migration "não aplicada")');

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, ativo) VALUES (:n,:c,:t,1)')
        ->execute(['n' => 'Cliente SelfHeal ' . date('His'), 'c' => '88.888.888/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();

    $model = new ClienteModel();
    $data = ['nome_empresa' => 'Cliente SelfHeal', 'CNPJ' => '88.888.888/0001-99', 'contato' => 'Contato', 'is_matriz' => 1, 'matriz_id' => null, 'logo_path' => null];
    $res = $model->updateWithStatusAudit($clienteId, $data, 0, 1, 'Teste de auto-reparo de schema', '127.0.0.1', 'test-agent');

    if (empty($res['ok'])) {
        throw new RuntimeException('Inativação deveria se auto-recuperar da tabela ausente, mas falhou: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
    }
    ok('Inativação funcionou mesmo com a tabela originalmente ausente (auto-reparo)');

    if (!Database::tableExists('cliente_status_logs')) {
        throw new RuntimeException('ensureColumns() deveria ter recriado cliente_status_logs.');
    }
    ok('Tabela cliente_status_logs foi recriada automaticamente');

    $stmt = $pdo->prepare('SELECT ativo FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $clienteId]);
    if ((int)$stmt->fetchColumn() !== 0) {
        throw new RuntimeException('Cliente não foi inativado após o auto-reparo.');
    }
    ok('Cliente foi efetivamente inativado após o auto-reparo');

    echo "clientes_status_logs_self_heal_smoke passed.\n";
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    $exitCode = 1;
} finally {
    if ($clienteId) {
        $pdo->prepare('DELETE FROM cliente_status_logs WHERE cliente_id = :id')->execute(['id' => $clienteId]);
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
    }
    // Descarta a tabela recriada pelo auto-reparo no teste e restaura a
    // tabela original com a DDL exata (FK incluída) + dados preservados.
    if (Database::tableExists($backupName)) {
        $pdo->exec('DROP TABLE IF EXISTS cliente_status_logs');
        $pdo->exec($originalDdl);
        $pdo->exec("INSERT INTO cliente_status_logs SELECT * FROM `{$backupName}`");
        $pdo->exec("DROP TABLE `{$backupName}`");
    }
}

exit($exitCode ?? 0);

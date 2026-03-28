<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Models\AuditoriaModel;

class FakeStmt
{
    private FakePDO $pdo;
    private string $sql;
    private int $rowCount = 0;
    private array $lastParams = [];

    public function __construct(FakePDO $pdo, string $sql)
    {
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    public function execute(array $params = []): bool
    {
        $this->lastParams = $params;
        if ($this->pdo->throwOnExecute && stripos($this->sql, $this->pdo->throwOnExecute) !== false) {
            throw new \PDOException('Simulated execute failure');
        }
        if (stripos($this->sql, 'UPDATE auditorias') !== false && stripos($this->sql, "status = 'Realizada'") !== false) {
            $this->rowCount = 1;
        } else {
            $this->rowCount = 0;
        }
        return true;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }

    public function fetch()
    {
        if (stripos($this->sql, 'FROM auditoria_avaliacoes') !== false && stripos($this->sql, 'SUM(') !== false) {
            return $this->pdo->sumRow;
        }
        return false;
    }
}

class FakePDO extends PDO
{
    public bool $inTx = false;
    public bool $rolledBack = false;
    public bool $committed = false;
    public bool $beginReturns = true;
    public ?string $throwOnExecute = null;
    public array $sumRow = ['total_conforme' => 1, 'total_nao_conforme' => 0, 'total_nao_aplica' => 0];

    public function beginTransaction(): bool
    {
        if (!$this->beginReturns) {
            return false;
        }
        $this->inTx = true;
        return true;
    }

    public function commit(): bool
    {
        if (!$this->inTx) {
            throw new \PDOException('There is no active transaction');
        }
        $this->committed = true;
        $this->inTx = false;
        return true;
    }

    public function rollBack(): bool
    {
        if (!$this->inTx) {
            throw new \PDOException('There is no active transaction');
        }
        $this->rolledBack = true;
        $this->inTx = false;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->inTx;
    }

    public function prepare($query, $options = null)
    {
        return new FakeStmt($this, (string)$query);
    }

    public function exec($statement): int|false
    {
        return 0;
    }
}

class TestAuditoriaModel extends AuditoriaModel
{
    public function __construct()
    {
    }

    public function find(int $id): ?array
    {
        return ['id' => $id, 'cliente_id' => 1, 'setor_id' => 1, 'status' => 'Agendada'];
    }
}

function setProtectedDb(object $model, PDO $pdo): void
{
    $ref = new ReflectionClass($model);
    $base = $ref;
    while ($base && !$base->hasProperty('db')) {
        $base = $base->getParentClass();
    }
    $p = $base->getProperty('db');
    $p->setAccessible(true);
    $p->setValue($model, $pdo);
}

function setTablesEnsuredTrue(): void
{
    $ref = new ReflectionClass(AuditoriaModel::class);
    $p = $ref->getProperty('tablesEnsured');
    $p->setAccessible(true);
    $p->setValue(null, true);
}

function assertTest($cond, $msg) {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
    }
}

$passed = 0;
$total = 0;

echo "Running Auditoria Finalizar Transaction Tests...\n";
echo "================================================\n";

setTablesEnsuredTrue();

echo "\nCase 1: beginTransaction returns false\n";
$pdo1 = (new ReflectionClass(FakePDO::class))->newInstanceWithoutConstructor();
$pdo1->beginReturns = false;
$model1 = (new ReflectionClass(TestAuditoriaModel::class))->newInstanceWithoutConstructor();
setProtectedDb($model1, $pdo1);
$ok = $model1->finalizarAuditoria(1, [['questao_id'=>1,'conformidade'=>'conforme','observacoes'=>'']], 1);
assertTest($ok === false, "Returns false when beginTransaction fails");
assertTest($pdo1->rolledBack === false, "Does not rollback without transaction");

echo "\nCase 2: exception during execute triggers safe rollback\n";
$pdo2 = (new ReflectionClass(FakePDO::class))->newInstanceWithoutConstructor();
$pdo2->throwOnExecute = 'INSERT INTO auditoria_avaliacoes';
$model2 = (new ReflectionClass(TestAuditoriaModel::class))->newInstanceWithoutConstructor();
setProtectedDb($model2, $pdo2);
$ok = $model2->finalizarAuditoria(2, [['questao_id'=>1,'conformidade'=>'conforme','observacoes'=>'']], 1);
assertTest($ok === false, "Returns false on execute exception");
assertTest($pdo2->rolledBack === true, "Rollback executed when transaction active");

echo "\nCase 3: happy path commits\n";
$pdo3 = (new ReflectionClass(FakePDO::class))->newInstanceWithoutConstructor();
$pdo3->sumRow = ['total_conforme' => 2, 'total_nao_conforme' => 1, 'total_nao_aplica' => 0];
$model3 = (new ReflectionClass(TestAuditoriaModel::class))->newInstanceWithoutConstructor();
setProtectedDb($model3, $pdo3);
$ok = $model3->finalizarAuditoria(3, [
    ['questao_id'=>1,'conformidade'=>'conforme','observacoes'=>''],
    ['questao_id'=>2,'conformidade'=>'nao_conforme','observacoes'=>''],
], 1);
assertTest($ok === true, "Returns true on success");
assertTest($pdo3->committed === true, "Commit executed");

echo "================================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);


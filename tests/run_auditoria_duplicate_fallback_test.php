<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Models\AuditoriaModel;

class FakePDO2 extends PDO {
    public bool $inTx=false; public bool $first=true; public array $rows=[]; public int $lastId=1000;
    public function beginTransaction(): bool { $this->inTx=true; return true; }
    public function commit(): bool { $this->inTx=false; return true; }
    public function rollBack(): bool { $this->inTx=false; return true; }
    public function inTransaction(): bool { return $this->inTx; }
    public function prepare($query, $options = null) {
        $self = $this;
        return new class($self, $query) {
            private $pdo; private $q;
            public function __construct($pdo,$q){ $this->pdo=$pdo;$this->q=$q; }
            public function execute($params=[]){
                // falha na primeira tentativa com status 'Rascunho' simulando enum inválido
                if (strpos($this->q,'INSERT INTO auditorias')!==false) {
                    if ($this->pdo->first && (($params['status'] ?? '')==='Rascunho')) { $this->pdo->first=false; throw new \PDOException('Data truncated for column status'); }
                    $this->pdo->rows[]=$params; $this->pdo->lastId++;
                }
                return true;
            }
        };
    }
    public function lastInsertId($name = null): string|int { return $this->lastId; }
    public function exec($statement): int|false { return 0; }
}

class TestModel extends AuditoriaModel { public function __construct(){} }

function setDb($model, PDO $pdo){ $r=new ReflectionClass($model); $p=$r->getParentClass()->getProperty('db'); $p->setAccessible(true); $p->setValue($model,$pdo); }
function setEnsured(){ $r=new ReflectionClass(AuditoriaModel::class); $p=$r->getProperty('tablesEnsured'); $p->setAccessible(true); $p->setValue(null,true); }

setEnsured();
$m=new TestModel();
$pdo=(new ReflectionClass(FakePDO2::class))->newInstanceWithoutConstructor();
setDb($m,$pdo);

$src=['id'=>1,'cliente_id'=>1,'setor_id'=>1,'data_auditoria'=>date('Y-m-d'),'nome_auditoria'=>'Origem','questoes'=>[['responsavel_nome'=>'X','pergunta'=>'P','referencia_esperada'=>'R','processos'=>[]]]];
$newId=$m->duplicateFrom($src,['nome_auditoria'=>'Copia','data_auditoria'=>date('Y-m-d')],1);

echo $newId>0 ? "[PASS] fallback enum status\n" : "[FAIL] fallback enum status\n";

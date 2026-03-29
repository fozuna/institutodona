<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Models\AuditoriaModel;

class FakePDODup extends PDO {
    public bool $inTx=false;
    public function beginTransaction(): bool { $this->inTx=true; return true; }
    public function commit(): bool { $this->inTx=false; return true; }
    public function rollBack(): bool { $this->inTx=false; return true; }
    public function inTransaction(): bool { return $this->inTx; }
    public function prepare($query, $options = null) {
        return new class($this, $query) {
            private $pdo; private $q; private $isSelect=false;
            public function __construct($pdo,$q){ $this->pdo=$pdo;$this->q=$q; $this->isSelect = stripos($q,'SELECT COUNT(*)')!==false; }
            public function execute($params=[]){
                if (strpos($this->q,'INSERT INTO auditorias')!==false) {
                    throw new \PDOException('Duplicate entry \'NOME\' for key \'nome_auditoria\'');
                }
                return true;
            }
            public function fetchColumn($col=0){ return $this->isSelect ? 0 : null; }
        };
    }
    public function exec($statement): int|false { return 0; }
}

class TestModelE extends AuditoriaModel { public function __construct(){} }

function setDbE($model, PDO $pdo){ $r=new ReflectionClass($model); $p=$r->getParentClass()->getProperty('db'); $p->setAccessible(true); $p->setValue($model,$pdo); }
function setEnsuredE(){ $r=new ReflectionClass(AuditoriaModel::class); $p=$r->getProperty('tablesEnsured'); $p->setAccessible(true); $p->setValue(null,true); }

setEnsuredE();
$m=new TestModelE();
$pdo=(new ReflectionClass(FakePDODup::class))->newInstanceWithoutConstructor();
setDbE($m,$pdo);

$src=['id'=>1,'cliente_id'=>1,'setor_id'=>1,'data_auditoria'=>date('Y-m-d'),'nome_auditoria'=>'Origem','questoes'=>[['responsavel_nome'=>'X','pergunta'=>'P','referencia_esperada'=>'R','processos'=>[]]]];
$id=$m->duplicateFrom($src,['nome_auditoria'=>'NOME','data_auditoria'=>date('Y-m-d')],1);

echo ($id===0 && strpos((string)$m->getLastError(),'Duplicate entry')!==false) ? "[PASS] duplicate error message propagated\n" : "[FAIL] duplicate error message not propagated\n";

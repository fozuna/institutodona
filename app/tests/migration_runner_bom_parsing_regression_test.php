<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

const BOM = "\xEF\xBB\xBF";

// --- Caso 1: migration normal, sem BOM ---
$sql = <<<SQL
-- 20260101_exemplo_apply.sql
-- comentario de descricao
CREATE TABLE exemplo (id INT PRIMARY KEY);
INSERT INTO exemplo (id) VALUES (1);
SQL;
$statements = MigrationRunner::parseStatements($sql);
assert_true(
    $statements === ['CREATE TABLE exemplo (id INT PRIMARY KEY)', 'INSERT INTO exemplo (id) VALUES (1)'],
    'Caso 1: migration sem BOM continua sendo parseada normalmente'
);

// --- Caso 2: migration iniciada com BOM UTF-8 seguida de comentario "--" ---
$sqlComBom = BOM . <<<SQL
-- 20260719165457_auto_schema_update_apply.sql
-- Nenhuma alteracao de schema real: apenas comentario descritivo.
SELECT 1;
SQL;
$statements = MigrationRunner::parseStatements($sqlComBom);
assert_true(
    $statements === ['SELECT 1'],
    'Caso 2: BOM + comentario "--" na primeira linha e reconhecido e descartado, sobrando so o SQL real'
);

// Reproduz literalmente o arquivo de producao que expos o bug.
$arquivoReal = __DIR__ . '/../database/migrations/20260719165457_auto_schema_update_apply.sql';
$conteudoReal = file_get_contents($arquivoReal);
assert_true(
    $conteudoReal !== false && str_starts_with($conteudoReal, BOM),
    'Pre-condicao: arquivo real 20260719165457_auto_schema_update_apply.sql ainda comeca com BOM (fixture de regressao valida)'
);
$statementsReais = MigrationRunner::parseStatements((string)$conteudoReal);
assert_true(
    $statementsReais === ['SELECT 1'],
    'Caso 2b: arquivo real com BOM e parseado corretamente (statement util isolado, sem lixo do comentario)'
);

// --- Caso 3: migration iniciada diretamente por SQL, sem comentario, sem BOM ---
$sqlDireto = "CREATE TABLE outra (id INT PRIMARY KEY);\nALTER TABLE outra ADD COLUMN nome VARCHAR(50);";
$statements = MigrationRunner::parseStatements($sqlDireto);
assert_true(
    $statements === ['CREATE TABLE outra (id INT PRIMARY KEY)', 'ALTER TABLE outra ADD COLUMN nome VARCHAR(50)'],
    'Caso 3: migration iniciada diretamente por SQL (sem comentario) continua funcionando'
);

// Mesmo caso, mas com BOM antes do SQL direto (sem comentario na primeira linha).
$sqlDiretoComBom = BOM . "CREATE TABLE outra (id INT PRIMARY KEY);\nALTER TABLE outra ADD COLUMN nome VARCHAR(50);";
$statements = MigrationRunner::parseStatements($sqlDiretoComBom);
assert_true(
    $statements === ['CREATE TABLE outra (id INT PRIMARY KEY)', 'ALTER TABLE outra ADD COLUMN nome VARCHAR(50)'],
    'Caso 3b: BOM antes de SQL direto (sem comentario) tambem e removido corretamente'
);

// --- Caso 4: normalizacao nao deve remover caracteres legitimos do SQL ---

// 4a. Acentuacao/UTF-8 multibyte no meio do conteudo deve ser preservada.
$sqlAcentuado = "-- comentario com acentuacao: nao mexer\nINSERT INTO exemplo (nome) VALUES ('Não atingida - configuração');";
$statements = MigrationRunner::parseStatements($sqlAcentuado);
assert_true(
    $statements === ["INSERT INTO exemplo (nome) VALUES ('Não atingida - configuração')"],
    'Caso 4a: caracteres UTF-8 multibyte legitimos (acentuacao) no corpo do SQL sao preservados'
);

// 4b. Uma string SQL que comeca com "--" (like um valor literal) so no INICIO do arquivo
// e removida (e o BOM), nunca no meio de uma linha ja processada como statement.
$sqlComTracoNoMeio = "CREATE TABLE exemplo (id INT PRIMARY KEY);\nINSERT INTO exemplo (id) VALUES (1); -- nao remover o resto desta linha se nao for comentario puro";
$statements = MigrationRunner::parseStatements($sqlComTracoNoMeio);
assert_true(
    $statements[0] === 'CREATE TABLE exemplo (id INT PRIMARY KEY)',
    'Caso 4b: primeiro statement nao e afetado por linhas de comentario adicionais'
);

// 4c. Sequencia de bytes identica ao BOM, mas NAO no inicio do arquivo, deve ser preservada
// (a remocao e estritamente prefixo, nunca uma limpeza global de bytes).
$sqlComBomNoMeio = "CREATE TABLE exemplo (id INT PRIMARY KEY);\nINSERT INTO exemplo (nome) VALUES ('" . BOM . "marcador');";
$statements = MigrationRunner::parseStatements($sqlComBomNoMeio);
assert_true(
    str_contains($statements[1], BOM),
    'Caso 4c: bytes iguais ao BOM fora do inicio do arquivo NAO sao removidos (remocao e so de prefixo)'
);

// 4d. Arquivo vazio ou so com BOM nao deve quebrar o parsing.
assert_true(
    MigrationRunner::parseStatements('') === [],
    'Caso 4d: SQL vazio retorna lista vazia de statements'
);
assert_true(
    MigrationRunner::parseStatements(BOM) === [],
    'Caso 4d: SQL contendo apenas o BOM retorna lista vazia de statements'
);

echo "MigrationRunner BOM parsing regression tests passed.\n";

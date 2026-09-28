<?php
// Regressão: com PDO::ATTR_EMULATE_PREPARES=false (app/database/Database.php) o PDO
// NÃO aceita o mesmo placeholder nomeado repetido na consulta: SQLSTATE[HY093].
// Isso derrubava a busca da listagem de Treinamentos (:q 3x), o autocomplete de
// responsáveis de Auditorias/Indicadores (:q 2x) e ClienteModel::groupCompanies
// (:root_id 2x). Este teste:
//  1) executa os três pontos contra o banco, com filtro textual, e confere o resultado;
//  2) varre os literais SQL de app/ e falha se algum repetir um placeholder nomeado.
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\TreinamentoModel;

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

// ---------- 2) Varredura estática ----------
// Arquivos que expandem placeholders repetidos antes do prepare() (trait
// PessoasEscopoSql::expandPlaceholders) podem repetir nomes com segurança.
$permitidos = [
    'models/PessoaOperacionalModel.php',
];
$literal = '/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'/s';
$sqlKeyword = '/\b(SELECT|UPDATE|INSERT|DELETE|WHERE|AND|OR|SET|VALUES|LIKE)\b/i';
$placeholder = '/(?<![:\w]):([a-zA-Z_]\w*)/';
$ocorrencias = [];
$appDir = realpath(__DIR__ . '/..');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $path = str_replace('\\', '/', $f->getPathname());
    if ($f->getExtension() !== 'php' || str_contains($path, '/tests/')) {
        continue;
    }
    $rel = ltrim(substr($path, strlen(str_replace('\\', '/', $appDir))), '/');
    if (in_array($rel, $permitidos, true)) {
        continue;
    }
    $src = (string)file_get_contents($path);
    if (!preg_match_all($literal, $src, $lits, PREG_OFFSET_CAPTURE)) {
        continue;
    }
    foreach ($lits[0] as [$texto, $offset]) {
        if (!preg_match($sqlKeyword, $texto) || !preg_match_all($placeholder, $texto, $ph)) {
            continue;
        }
        $repetidos = array_filter(array_count_values($ph[1]), static fn(int $n): bool => $n > 1);
        if ($repetidos) {
            $linha = substr_count(substr($src, 0, $offset), "\n") + 1;
            $ocorrencias[] = $rel . ':' . $linha . ' ' . json_encode($repetidos);
        }
    }
}
if ($ocorrencias) {
    failFast("Placeholder nomeado repetido na mesma consulta (HY093 com prepares nativos):\n  " . implode("\n  ", $ocorrencias));
}
ok('Nenhum literal SQL em app/ repete placeholder nomeado');

// ---------- 1) Execução real ----------
$pdo = Database::getConnection();
$sfx = 'hy093_' . bin2hex(random_bytes(3));
$ids = ['clientes' => [], 'departamentos' => [], 'setores' => [], 'funcoes' => [], 'colaboradores' => [], 'treinamentos' => []];

try {
    Auth::login(['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n, :c, :ct, :m, :mid)');
    $insCli->execute(['n' => 'Matriz ' . $sfx, 'c' => '11.' . substr($sfx, -6), 'ct' => 'x', 'm' => 1, 'mid' => null]);
    $matriz = (int)$pdo->lastInsertId();
    $ids['clientes'][] = $matriz;
    $insCli->execute(['n' => 'Filial ' . $sfx, 'c' => '12.' . substr($sfx, -6), 'ct' => 'x', 'm' => 0, 'mid' => $matriz]);
    $filial = (int)$pdo->lastInsertId();
    $ids['clientes'][] = $filial;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n, :c)')->execute(['n' => 'Dep ' . $sfx, 'c' => $matriz]);
    $dep = (int)$pdo->lastInsertId();
    $ids['departamentos'][] = $dep;
    $pdo->prepare('INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d, :c)')->execute(['d' => $dep, 'c' => $matriz]);
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n, :d)')->execute(['n' => 'Setor ' . $sfx, 'd' => $dep]);
    $setor = (int)$pdo->lastInsertId();
    $ids['setores'][] = $setor;
    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n, :s)')->execute(['n' => 'Funcao ' . $sfx, 's' => $setor]);
    $funcao = (int)$pdo->lastInsertId();
    $ids['funcoes'][] = $funcao;
    $insCol = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n, :e, :f, :c)');
    $insCol->execute(['n' => 'Pessoa Nome ' . $sfx, 'e' => 'nome.' . $sfx . '@test.local', 'f' => $funcao, 'c' => $matriz]);
    $ids['colaboradores'][] = (int)$pdo->lastInsertId();
    $insCol->execute(['n' => 'Outra Pessoa', 'e' => 'email.' . $sfx . '@test.local', 'f' => $funcao, 'c' => $matriz]);
    $ids['colaboradores'][] = (int)$pdo->lastInsertId();

    $tm = new TreinamentoModel();
    $base = ['objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '2', 'cliente_id' => $matriz, 'departamento_id' => $dep,
        'periodicidade' => 'anual', 'fornecedor' => 'Fornecedor', 'tipo_treinamento' => 'Interno', 'template_certificado' => '',
        'assinatura_responsavel' => 'Gestor', 'setor_ids' => [], 'funcao_ids' => []];
    foreach ([
        ['nome' => 'TrNome ' . $sfx],
        ['nome' => 'Treinamento B', 'objetivo' => 'TrObjetivo ' . $sfx],
        ['nome' => 'Treinamento C', 'fornecedor' => 'TrFornecedor ' . $sfx],
        ['nome' => 'Treinamento D'],
    ] as $over) {
        $tid = $tm->create(array_merge($base, $over));
        if ($tid <= 0) { failFast('Falha ao criar treinamento de fixture'); }
        $ids['treinamentos'][] = $tid;
    }

    // Treinamentos: busca casa em nome, objetivo OU fornecedor.
    try {
        $filtros = ['cliente_id' => $matriz, 'q' => $sfx];
        $total = $tm->countIndex($filtros);
        $lista = $tm->paginateIndex($filtros, 1, 10);
    } catch (\PDOException $e) {
        failFast('Busca da listagem de Treinamentos lançou exceção: ' . $e->getMessage());
    }
    if ($total !== 3 || count($lista) !== 3) {
        failFast("Busca de Treinamentos deveria achar 3 (nome, objetivo, fornecedor); total=$total itens=" . count($lista));
    }
    ok('Busca da listagem de Treinamentos funciona e cobre nome, objetivo e fornecedor');

    // Autocomplete de colaboradores: busca casa em nome OU e-mail.
    try {
        $achados = (new ColaboradorModel())->searchActiveByCliente($matriz, $sfx, 15);
    } catch (\PDOException $e) {
        failFast('Autocomplete de colaboradores lançou exceção: ' . $e->getMessage());
    }
    $nomes = array_column($achados, 'nome');
    sort($nomes);
    if ($nomes !== ['Outra Pessoa', 'Pessoa Nome ' . $sfx]) {
        failFast('Autocomplete deveria achar por nome e por e-mail: ' . json_encode($nomes));
    }
    if (count((new ColaboradorModel())->searchActiveByCliente($matriz, 'inexistente-' . $sfx, 15)) !== 0) {
        failFast('Autocomplete não deveria retornar resultados para termo inexistente');
    }
    ok('Autocomplete de colaboradores (Auditorias/Indicadores) funciona por nome e e-mail');

    // Grupo empresarial.
    try {
        $grupo = array_map('intval', array_column((new ClienteModel())->groupCompanies($filial), 'id'));
    } catch (\PDOException $e) {
        failFast('ClienteModel::groupCompanies lançou exceção: ' . $e->getMessage());
    }
    sort($grupo);
    if ($grupo !== [$matriz, $filial]) {
        failFast('groupCompanies deveria retornar matriz e filial: ' . json_encode($grupo));
    }
    ok('ClienteModel::groupCompanies retorna matriz + filiais');
} finally {
    $del = static function (string $tabela, array $lista) use ($pdo): void {
        foreach ($lista as $id) {
            try { $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?")->execute([$id]); } catch (\Throwable $e) {}
        }
    };
    $del('treinamentos', $ids['treinamentos']);
    $del('colaboradores', $ids['colaboradores']);
    $del('funcoes', $ids['funcoes']);
    $del('setores', $ids['setores']);
    $del('departamentos', $ids['departamentos']);
    $del('clientes', array_reverse($ids['clientes']));
}

echo "\nPDO placeholders duplicados regression passed.\n";

<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\ManualModel;
use App\Models\SetorModel;
use App\Models\TreinamentoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "SKIP: sem conexão com o banco para testes de guardrails de catálogo.\n";
    exit(0);
}

$suffix = 'grp_guard_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$manualIds = [];
$auditoriaIds = [];
$treinamentoIds = [];

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n, :c, :ct, :m, :mid)');
    $insCli->execute([
        'n' => 'Matriz Guard ' . $suffix,
        'c' => '77.777.777/0001-' . random_int(10, 99),
        'ct' => 'Contato',
        'm' => 1,
        'mid' => null,
    ]);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $insCli->execute([
        'n' => 'Filial Guard ' . $suffix,
        'c' => '77.777.777/0002-' . random_int(10, 99),
        'ct' => 'Contato',
        'm' => 0,
        'mid' => $matrizId,
    ]);
    $filialId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialId;

    $insCli->execute([
        'n' => 'Empresa Externa ' . $suffix,
        'c' => '88.888.888/0001-' . random_int(10, 99),
        'ct' => 'Contato',
        'm' => 1,
        'mid' => null,
    ]);
    $outroGrupoId = (int)$pdo->lastInsertId();
    $clienteIds[] = $outroGrupoId;

    Auth::login([
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => null,
    ]);

    $departamentos = new DepartamentoModel();
    $setores = new SetorModel();
    $funcoes = new FuncaoModel();
    $manuais = new ManualModel();
    $auditorias = new AuditoriaModel();
    $treinamentos = new TreinamentoModel();

    $depMatrizId = $departamentos->create(['nome' => 'Departamento Matriz ' . $suffix, 'cliente_id' => $matrizId]);
    $depFilialResolvedId = $departamentos->create(['nome' => 'Departamento Filial ' . $suffix, 'cliente_id' => $filialId]);
    $depOutroId = $departamentos->create(['nome' => 'Departamento Outro ' . $suffix, 'cliente_id' => $outroGrupoId]);
    $departamentoIds = array_filter([$depMatrizId, $depFilialResolvedId, $depOutroId]);
    if ($depMatrizId <= 0 || $depFilialResolvedId <= 0 || $depOutroId <= 0) {
        failFast('Falha ao criar departamentos de teste');
    }

    $depFilialResolved = $departamentos->find($depFilialResolvedId);
    if ((int)($depFilialResolved['cliente_id'] ?? 0) !== $matrizId) {
        failFast('Departamento criado pela filial deveria ser reatribuído ao catálogo da matriz');
    }
    ok('Departamento criado pela filial resolve automaticamente para a matriz');

    $setorMatrizId = $setores->create(['nome' => 'Setor Matriz ' . $suffix, 'departamento_id' => $depMatrizId]);
    $setorFilialResolvedId = $setores->create(['nome' => 'Setor Filial ' . $suffix, 'departamento_id' => $depFilialResolvedId]);
    $setorOutroId = $setores->create(['nome' => 'Setor Outro ' . $suffix, 'departamento_id' => $depOutroId]);
    $setorIds = array_filter([$setorMatrizId, $setorFilialResolvedId, $setorOutroId]);
    if ($setorMatrizId <= 0 || $setorFilialResolvedId <= 0 || $setorOutroId <= 0) {
        failFast('Falha ao criar setores de teste');
    }

    $funcaoMatrizId = $funcoes->create(['nome' => 'Funcao Matriz ' . $suffix, 'setor_id' => $setorMatrizId]);
    $funcaoOutroId = $funcoes->create(['nome' => 'Funcao Outro ' . $suffix, 'setor_id' => $setorOutroId]);
    $funcaoIds = array_filter([$funcaoMatrizId, $funcaoOutroId]);
    if ($funcaoMatrizId <= 0 || $funcaoOutroId <= 0) {
        failFast('Falha ao criar funções de teste');
    }

    $manualFilialValido = $manuais->create([
        'empresa_id' => $filialId,
        'departamento_id' => $depMatrizId,
        'nome' => 'Manual Valido ' . $suffix,
        'descricao' => 'Mesmo grupo',
        'arquivo' => 'storage/manuais/' . $filialId . '/' . $depMatrizId . '/ok.pdf',
        'tipo_arquivo' => 'pdf',
        'tamanho' => 10,
        'usuario_id' => 1,
    ]);
    $manualIds[] = $manualFilialValido;
    if ($manualFilialValido <= 0) {
        failFast('Manual da filial com departamento do catálogo da matriz deveria ser permitido');
    }
    ok('Manual da filial aceita departamento do catálogo da matriz');

    $manualFilialInvalido = $manuais->create([
        'empresa_id' => $filialId,
        'departamento_id' => $depOutroId,
        'nome' => 'Manual Invalido ' . $suffix,
        'descricao' => 'Outro grupo',
        'arquivo' => 'storage/manuais/' . $filialId . '/' . $depOutroId . '/erro.pdf',
        'tipo_arquivo' => 'pdf',
        'tamanho' => 10,
        'usuario_id' => 1,
    ]);
    if ($manualFilialInvalido !== 0) {
        failFast('Manual da filial não deveria aceitar departamento de outro grupo');
    }
    ok('Manual bloqueia departamento de outro grupo');

    $auditoriaValida = $auditorias->create([
        'cliente_id' => $filialId,
        'setor_id' => $setorMatrizId,
        'data_auditoria' => date('Y-m-d'),
        'nome_auditoria' => 'Auditoria Valida ' . $suffix,
        'questoes' => [[
            'pergunta' => 'Pergunta 1',
            'referencia_esperada' => 'Referencia 1',
            'responsavel_nome' => 'Responsavel 1',
            'responsavel_ids' => [],
        ]],
    ], 1);
    $auditoriaIds[] = $auditoriaValida;
    if ($auditoriaValida <= 0) {
        failFast('Auditoria da filial com setor do catálogo da matriz deveria ser permitida');
    }
    ok('Auditoria aceita setor do catálogo da matriz');

    $auditoriaInvalida = $auditorias->create([
        'cliente_id' => $filialId,
        'setor_id' => $setorOutroId,
        'data_auditoria' => date('Y-m-d'),
        'nome_auditoria' => 'Auditoria Invalida ' . $suffix,
        'questoes' => [[
            'pergunta' => 'Pergunta 2',
            'referencia_esperada' => 'Referencia 2',
            'responsavel_nome' => 'Responsavel 2',
            'responsavel_ids' => [],
        ]],
    ], 1);
    if ($auditoriaInvalida !== 0) {
        failFast('Auditoria da filial não deveria aceitar setor de outro grupo');
    }
    ok('Auditoria bloqueia setor de outro grupo');

    $treinamentoValido = $treinamentos->create([
        'nome' => 'Treinamento Valido ' . $suffix,
        'objetivo' => 'Mesmo grupo',
        'publico' => 'Todos',
        'carga_horaria' => '4',
        'departamento_id' => $depMatrizId,
        'setor_ids' => [$setorMatrizId],
        'funcao_ids' => [$funcaoMatrizId],
        'periodicidade' => 'avulso',
        'fornecedor' => 'Interno',
        'tipo_treinamento' => 'Online',
        'template_certificado' => '',
        'assinatura_responsavel' => '',
    ]);
    $treinamentoIds[] = $treinamentoValido;
    if ($treinamentoValido <= 0) {
        failFast('Treinamento com departamento/setor/função do mesmo catálogo deveria ser permitido');
    }
    ok('Treinamento aceita vínculos coerentes com o catálogo');

    $treinamentoInvalido = $treinamentos->create([
        'nome' => 'Treinamento Invalido ' . $suffix,
        'objetivo' => 'Outro grupo',
        'publico' => 'Todos',
        'carga_horaria' => '4',
        'departamento_id' => $depMatrizId,
        'setor_ids' => [$setorMatrizId],
        'funcao_ids' => [$funcaoOutroId],
        'periodicidade' => 'avulso',
        'fornecedor' => 'Interno',
        'tipo_treinamento' => 'Online',
        'template_certificado' => '',
        'assinatura_responsavel' => '',
    ]);
    if ($treinamentoInvalido !== 0) {
        failFast('Treinamento não deveria aceitar função de outro grupo');
    }
    ok('Treinamento bloqueia função de outro grupo');

    echo "catalogo_grupo_relacionamentos_guardrails_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    try {
        if (!empty($treinamentoIds)) {
            $in = implode(',', array_map('intval', $treinamentoIds));
            $pdo->exec("DELETE FROM treinamento_funcoes WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamento_setores WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamento_colaboradores WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamentos_agenda WHERE treinamento_id IN ($in)");
            $pdo->exec("DELETE FROM treinamentos WHERE id IN ($in)");
        }
        if (!empty($auditoriaIds)) {
            $in = implode(',', array_map('intval', $auditoriaIds));
            $pdo->exec("DELETE FROM auditoria_questao_responsaveis WHERE questao_id IN (SELECT id FROM auditoria_questoes WHERE auditoria_id IN ($in))");
            $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
            $pdo->exec("DELETE FROM auditoria_responsaveis WHERE auditoria_id IN ($in)");
            $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
            $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
        }
        if (!empty($manualIds)) {
            $in = implode(',', array_map('intval', array_filter($manualIds)));
            if ($in !== '') {
                $pdo->exec("DELETE FROM manual_filial_links WHERE manual_id IN ($in)");
                $pdo->exec("DELETE FROM manuais WHERE id IN ($in)");
            }
        }
        if (!empty($funcaoIds)) {
            $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', $funcaoIds)) . ')');
        }
        if (!empty($setorIds)) {
            $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', $setorIds)) . ')');
        }
        if (!empty($departamentoIds)) {
            $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $departamentoIds)) . ')');
        }
        if (!empty($clienteIds)) {
            $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
        }
    } catch (Throwable $e) {
    }
    Auth::logout();
}

<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Core\PdfSupport;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\CronogramaEventoModel;
use App\Models\CronogramaEventoTipoModel;
use App\Models\CronogramaModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\SetorModel;
use App\Models\TarefaModel;

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
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'tarefa_id' => 0, 'cronograma_id' => 0, 'evento_id' => 0, 'indicador_id' => 0,
    'setor_id' => 0, 'departamento_id' => 0, 'unidade_id' => 0, 'cliente_id' => 0,
    'outro_cliente_id' => 0,
];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        if (!empty($cleanup['tarefa_id'])) { $pdo->prepare('DELETE FROM tarefas WHERE id = :id')->execute(['id' => $cleanup['tarefa_id']]); }
        if (!empty($cleanup['evento_id'])) { $pdo->prepare('DELETE FROM cronograma_eventos WHERE id = :id')->execute(['id' => $cleanup['evento_id']]); }
        if (!empty($cleanup['cronograma_id'])) { $pdo->prepare('DELETE FROM cronogramas WHERE id = :id')->execute(['id' => $cleanup['cronograma_id']]); }
        if (!empty($cleanup['indicador_id'])) {
            $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $cleanup['indicador_id']]);
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]);
        }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
        if (!empty($cleanup['outro_cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['outro_cliente_id']]); }
    } catch (\Throwable $e) {
    }
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$cronogramas = new CronogramaModel();
$eventos = new CronogramaEventoModel();
$tipos = new CronogramaEventoTipoModel();
$tarefas = new TarefaModel();
$indicadorModel = new IndicadorModel();

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Resumo Mes ' . $suffix,
    'CNPJ' => '33.444.555/0001-' . substr($suffix, 0, 2),
    'contato' => 'Teste',
]);
ok('Criou cliente para o teste do Resumo do Mês');
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;

$outroClienteId = $clientes->create([
    'nome_empresa' => 'Cliente Resumo Mes Outro ' . $suffix,
    'CNPJ' => '33.444.556/0001-' . substr($suffix, 0, 2),
    'contato' => 'Teste',
]);
if ($outroClienteId <= 0) failFast('Falha ao criar segundo cliente (sem dados) para o teste de isolamento');
$cleanup['outro_cliente_id'] = $outroClienteId;

$departamentoId = $departamentos->create(['nome' => 'Dep Resumo ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorId = $setores->create(['nome' => 'Setor Resumo ' . $suffix, 'departamento_id' => $departamentoId]);
$cleanup['departamento_id'] = $departamentoId;
$cleanup['setor_id'] = $setorId;
ok('Criou departamento e setor');

// 1) Cronograma: evento finalizado dentro do mês filtrado.
$tipoEvento = 'Tipo Resumo ' . uniqid();
$tipos->create($tipoEvento);
$cronogramaId = $cronogramas->create(['id_cliente' => $clienteId, 'nome' => 'Cronograma Resumo ' . $suffix, 'ano' => 2026]);
if ($cronogramaId <= 0) failFast('Falha ao criar cronograma');
$cleanup['cronograma_id'] = $cronogramaId;
$eventoId = $eventos->create($cronogramaId, [
    'topico' => 'Topico Resumo ' . $suffix,
    'unidade' => 'Matriz',
    'atividade' => 'Atividade concluída no mês ' . $suffix,
    'responsavel' => 'Fulano',
    'periodicidade' => 'unico',
    'data' => '2026-07-15',
    'tipo_evento' => $tipoEvento,
]);
if ($eventoId <= 0) failFast('Falha ao criar evento de cronograma');
$cleanup['evento_id'] = $eventoId;
if (!$eventos->setStatus($eventoId, 'Finalizado')) failFast('Falha ao finalizar evento de cronograma');
ok('Criou e finalizou evento de cronograma dentro do mês filtrado');

// Evento fora do período (mês seguinte) não deve ser contabilizado.
$eventoForaId = $eventos->create($cronogramaId, [
    'topico' => 'Topico Fora ' . $suffix,
    'unidade' => 'Matriz',
    'atividade' => 'Atividade fora do periodo ' . $suffix,
    'responsavel' => 'Fulano',
    'periodicidade' => 'unico',
    'data' => '2026-08-15',
    'tipo_evento' => $tipoEvento,
]);
$eventos->setStatus($eventoForaId, 'Finalizado');

// 2) Tarefa concluída dentro do mês (finalizado_em = NOW(), então o teste roda "no mês corrente" simulado).
$tarefaId = $tarefas->create([
    'cliente_id' => $clienteId,
    'titulo' => 'Tarefa Resumo ' . $suffix,
    'descricao' => 'Descricao',
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-t'),
    'prioridade' => 'media',
    'status' => 'Planejado',
]);
if ($tarefaId <= 0) failFast('Falha ao criar tarefa');
$cleanup['tarefa_id'] = $tarefaId;
if (!$tarefas->finalize($tarefaId, 1)) failFast('Falha ao finalizar tarefa');
ok('Criou e finalizou tarefa no mês corrente');

// 3) Indicador lançado no mês corrente (lancado_em = NOW() via updateAchievedValue/updateValor).
$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:n, :s, :t, 1)');
$stmt->execute(['n' => 'Unidade Resumo ' . $suffix, 's' => 'un', 't' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
if ($unidadeId <= 0) failFast('Falha ao criar unidade de medida');
$cleanup['unidade_id'] = $unidadeId;
$indicadorPayload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador Resumo ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $indicadorModel->validate($indicadorPayload);
if ($errors) failFast('Payload de indicador inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $indicadorModel->create($indicadorPayload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_id'] = $indicadorId;

$indicadorEventos = new IndicadorEventoModel();
$stmtEvt = $pdo->prepare('SELECT id FROM indicador_eventos WHERE indicador_id = :id ORDER BY id LIMIT 1');
$stmtEvt->execute(['id' => $indicadorId]);
$indicadorEventoId = (int)$stmtEvt->fetchColumn();
if ($indicadorEventoId <= 0) failFast('Nenhum evento de indicador foi gerado automaticamente');
if (!$indicadorEventos->updateAchievedValue($indicadorEventoId, '55,00', 1)) failFast('Falha ao lançar valor do indicador');
ok('Criou indicador e lançou valor no evento gerado automaticamente, no mês corrente');

// --- Verificações ---

$currentMonth = date('Y-m');

// A) Cronograma: mês de julho/2026 deve trazer só o evento finalizado dentro do período.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'dashboard/resumo_mes', 'month_start' => '2026-07', 'month_end' => '2026-07', 'clientes' => [(string)$clienteId]];
ob_start();
(new DashboardController())->resumoMes();
$html = (string)ob_get_clean();
if (!str_contains($html, 'Resumo do Mês')) failFast('Tela não renderizou o título');
ok('Tela do Resumo do Mês renderiza corretamente');
if (!str_contains($html, 'Atividade concluída no mês ' . $suffix)) failFast('Cronograma: atividade do mês filtrado não apareceu na lista');
ok('Cronograma: atividade finalizada no mês filtrado aparece na lista');
if (str_contains($html, 'Atividade fora do periodo ' . $suffix)) failFast('Cronograma: atividade de outro mês vazou para o relatório');
ok('Cronograma: atividade fora do período filtrado não aparece (filtro de data correto)');

// B) Tarefas e indicadores: usando o mês corrente (onde finalizado_em/lancado_em foram gravados agora).
$_GET = ['route' => 'dashboard/resumo_mes', 'month_start' => $currentMonth, 'month_end' => $currentMonth, 'clientes' => [(string)$clienteId]];
ob_start();
(new DashboardController())->resumoMes();
$htmlAtual = (string)ob_get_clean();
if (!str_contains($htmlAtual, 'Tarefa Resumo ' . $suffix)) failFast('Tarefas: tarefa finalizada no mês corrente não apareceu');
ok('Tarefas: tarefa concluída no mês corrente aparece na lista');
if (!str_contains($htmlAtual, 'Indicador Resumo ' . $suffix)) failFast('Indicadores: lançamento do mês corrente não apareceu');
ok('Indicadores: lançamento do mês corrente aparece na lista');

// C) Isolamento multiempresa: o módulo dashboard/* é exclusivo do perfil "instituto"
// no RBAC (nenhum outro perfil tem o módulo ADMIN liberado), então a checagem
// relevante aqui é que o filtro por empresa realmente restringe os dados —
// filtrando por uma empresa SEM dados, os dados da outra empresa não devem vazar.
$_GET = ['route' => 'dashboard/resumo_mes', 'month_start' => '2026-07', 'month_end' => '2026-07', 'clientes' => [(string)$outroClienteId]];
ob_start();
(new DashboardController())->resumoMes();
$htmlOutraEmpresa = (string)ob_get_clean();
if (str_contains($htmlOutraEmpresa, 'Atividade concluída no mês ' . $suffix)) failFast('Vazamento entre empresas: filtro por outra empresa mostrou dados da empresa de teste');
ok('Filtro por empresa: dados de uma empresa não vazam para o relatório filtrado por outra empresa');

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível — pulando verificação de preview/PDF binário.\n";
} else {
    // D) Preview HTML do PDF.
    $_GET = ['route' => 'dashboard/resumo_mes_pdf', 'month_start' => '2026-07', 'month_end' => '2026-07', 'clientes' => [(string)$clienteId], 'preview' => '1'];
    ob_start();
    (new DashboardController())->resumoMesPdf();
    $preview = (string)ob_get_clean();
    if (!str_contains($preview, '<html')) failFast('Preview do PDF não gerou HTML válido');
    ok('Preview do PDF gera HTML válido');
    if (!str_contains($preview, 'Atividade concluída no mês ' . $suffix)) failFast('Preview do PDF não contém a atividade esperada');
    ok('Preview do PDF contém os dados esperados');

    // E) PDF binário real.
    unset($_GET['preview']);
    header_remove();
    ob_start();
    (new DashboardController())->resumoMesPdf();
    $pdf = (string)ob_get_clean();
    if (substr($pdf, 0, 4) !== '%PDF') failFast('resumo_mes_pdf não retornou um PDF binário válido');
    ok('resumo_mes_pdf retorna um PDF binário válido');
    if (strlen($pdf) <= 1200) failFast('PDF gerado tem tamanho suspeito de vazio');
    ok('PDF gerado tem tamanho mínimo plausível');
}

echo "Dashboard resumo do mês smoke tests passed.\n";
ob_end_flush();

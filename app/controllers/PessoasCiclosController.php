<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaCicloAvaliacaoModel;
use App\Models\PessoaGapModel;
use App\Models\PessoaModeloAvaliacaoModel;

/**
 * Pilar de Pessoas - Avaliação de Desempenho: Ciclos, Participantes e o
 * fluxo de preenchimento/finalização da Avaliação individual. Acesso
 * restrito a Instituto/Cliente Admin, sempre tenant-bound.
 */
class PessoasCiclosController extends BaseController
{
    private PessoaCicloAvaliacaoModel $ciclos;
    private PessoaModeloAvaliacaoModel $modelos;
    private PessoaAvaliacaoModel $avaliacoes;
    private ClienteModel $clientes;
    private ColaboradorModel $colaboradores;
    private DepartamentoModel $departamentos;
    private PessoaGapModel $gaps;

    public function __construct()
    {
        $this->ciclos = new PessoaCicloAvaliacaoModel();
        $this->modelos = new PessoaModeloAvaliacaoModel();
        $this->avaliacoes = new PessoaAvaliacaoModel();
        $this->clientes = new ClienteModel();
        $this->colaboradores = new ColaboradorModel();
        $this->departamentos = new DepartamentoModel();
        $this->gaps = new PessoaGapModel();
    }

    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $empresaId = (int)($this->resolveScopedClienteId((int)($_GET['empresa_id'] ?? 0) ?: null) ?? 0);
        $clientes = $this->clientes->all();
        $items = $empresaId > 0 ? $this->ciclos->listByEmpresa($empresaId) : [];
        $this->render('pessoas/ciclos/index', [
            'pageTitle' => 'Avaliações de Desempenho',
            'clientes' => $clientes,
            'selectedEmpresa' => $empresaId,
            'items' => $items,
        ]);
    }

    public function create(): void
    {
        $this->requireClienteAdminAccess();
        $empresaId = (int)($this->resolveScopedClienteId((int)($_GET['empresa_id'] ?? 0) ?: null) ?? 0);
        $modelosDisponiveis = $empresaId > 0 ? array_values(array_filter($this->modelos->listByEmpresa($empresaId), static fn(array $m): bool => (int)$m['ativo'] === 1)) : [];
        $this->render('pessoas/ciclos/create', [
            'pageTitle' => 'Novo Ciclo de Avaliação',
            'clientes' => $this->clientes->all(),
            'selectedEmpresa' => $empresaId,
            'modelosDisponiveis' => $modelosDisponiveis,
        ]);
    }

    public function store(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $empresaId = (int)($this->resolveScopedClienteId((int)($_POST['empresa_id'] ?? 0) ?: null) ?? 0);
        $id = $this->ciclos->create([
            'empresa_id' => $empresaId,
            'modelo_id' => (int)($_POST['modelo_id'] ?? 0),
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_fim' => $_POST['data_fim'] ?? null,
            'created_by' => (int)($_SESSION['user']['id'] ?? 0),
        ], $this->modelos);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível criar o ciclo. Verifique Empresa, Modelo e Nome.';
            $this->redirect('index.php?route=pessoas/cicloCreate&empresa_id=' . $empresaId);
            return;
        }
        AuditLogger::log('pessoas_ciclo_criado', 'pessoas_ciclo', $id, ['empresa_id' => $empresaId]);
        $_SESSION['flash_success'] = 'Ciclo criado como rascunho. Selecione os participantes.';
        $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
    }

    public function edit(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            $_SESSION['flash_error'] = 'Ciclo não encontrado.';
            $this->redirect('index.php?route=pessoas/index');
            return;
        }
        $modelosDisponiveis = array_values(array_filter($this->modelos->listByEmpresa((int)$ciclo['empresa_id']), static fn(array $m): bool => (int)$m['ativo'] === 1 || (int)$m['id'] === (int)$ciclo['modelo_id']));
        $this->render('pessoas/ciclos/edit', [
            'pageTitle' => 'Editar Ciclo de Avaliação',
            'ciclo' => $ciclo,
            'modelosDisponiveis' => $modelosDisponiveis,
        ]);
    }

    public function update(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            http_response_code(404);
            echo 'Ciclo não encontrado.';
            return;
        }
        $ok = $this->ciclos->update($id, (int)$ciclo['empresa_id'], [
            'modelo_id' => (int)($_POST['modelo_id'] ?? $ciclo['modelo_id']),
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_fim' => $_POST['data_fim'] ?? null,
        ], $this->modelos);
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível salvar (verifique se o ciclo ainda está em rascunho).';
            $this->redirect('index.php?route=pessoas/cicloEdit&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_ciclo_alterado', 'pessoas_ciclo', $id, ['empresa_id' => $ciclo['empresa_id']]);
        $_SESSION['flash_success'] = 'Ciclo atualizado.';
        $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
    }

    /** Tela principal do ciclo: em rascunho mostra o seletor de participantes; aberto/encerrado mostra progresso. */
    public function show(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            $_SESSION['flash_error'] = 'Ciclo não encontrado.';
            $this->redirect('index.php?route=pessoas/index');
            return;
        }
        $participantes = $this->ciclos->listParticipants($id);
        $colaboradoresDisponiveis = [];
        $departamentos = [];
        if ($ciclo['status'] === 'rascunho') {
            $colaboradoresDisponiveis = $this->colaboradores->allByCliente((int)$ciclo['empresa_id']);
            $departamentos = $this->departamentos->allByCliente((int)$ciclo['empresa_id']);
        }
        $participanteIds = array_map(static fn(array $p): int => (int)$p['colaborador_id'], $participantes);
        $this->render('pessoas/ciclos/show', [
            'pageTitle' => 'Ciclo: ' . $ciclo['nome'],
            'ciclo' => $ciclo,
            'participantes' => $participantes,
            'participanteIds' => $participanteIds,
            'colaboradoresDisponiveis' => $colaboradoresDisponiveis,
            'departamentos' => $departamentos,
        ]);
    }

    public function participantesStore(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            http_response_code(404);
            echo 'Ciclo não encontrado.';
            return;
        }
        $colaboradorIds = (array)($_POST['colaborador_ids'] ?? []);
        $result = $this->ciclos->replaceParticipants($id, (int)$ciclo['empresa_id'], $colaboradorIds, $this->colaboradores);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = 'Não foi possível salvar os participantes (ciclo precisa estar em rascunho).';
            $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
            return;
        }
        // Mantém exatamente uma avaliação "pendente" por participante atual (invariante da Sprint).
        $validIds = [];
        foreach ($this->ciclos->listParticipants($id) as $p) {
            $validIds[] = (int)$p['colaborador_id'];
        }
        $this->avaliacoes->ensureForParticipants($id, (int)$ciclo['empresa_id'], $validIds);

        $_SESSION['flash_success'] = $result['added'] . ' participante(s) salvo(s)' . ($result['ignored'] > 0 ? ' (' . $result['ignored'] . ' ignorado(s) por não pertencer à empresa)' : '') . '.';
        $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
    }

    public function abrir(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            http_response_code(404);
            echo 'Ciclo não encontrado.';
            return;
        }
        if (!$this->ciclos->abrir($id, (int)$ciclo['empresa_id'])) {
            $_SESSION['flash_error'] = 'Não foi possível abrir o ciclo (precisa estar em rascunho e ter ao menos 1 participante).';
            $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_ciclo_aberto', 'pessoas_ciclo', $id, ['empresa_id' => $ciclo['empresa_id']]);
        $_SESSION['flash_success'] = 'Ciclo aberto. As avaliações já podem ser realizadas.';
        $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
    }

    public function encerrar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $ciclo = $this->ciclos->find($id);
        if (!$ciclo) {
            http_response_code(404);
            echo 'Ciclo não encontrado.';
            return;
        }
        if (!$this->ciclos->encerrar($id, (int)$ciclo['empresa_id'])) {
            $_SESSION['flash_error'] = 'Não foi possível encerrar o ciclo (precisa estar aberto).';
            $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_ciclo_encerrado', 'pessoas_ciclo', $id, ['empresa_id' => $ciclo['empresa_id']]);
        $_SESSION['flash_success'] = 'Ciclo encerrado.';
        $this->redirect('index.php?route=pessoas/cicloShow&id=' . $id);
    }

    // ---------------------------------------------------------------
    // Avaliação individual
    // ---------------------------------------------------------------

    public function avaliacaoIniciar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $avaliacao = $this->avaliacoes->find($id);
        if (!$avaliacao) {
            http_response_code(404);
            echo 'Avaliação não encontrada.';
            return;
        }
        $usuarioId = (int)($_SESSION['user']['id'] ?? 0);
        $result = $this->avaliacoes->iniciar($id, (int)$avaliacao['empresa_id'], $usuarioId, $this->modelos);
        if ($result === null) {
            $_SESSION['flash_error'] = 'Não foi possível iniciar a avaliação (o ciclo precisa estar aberto e o modelo precisa ter perguntas).';
            $this->redirect('index.php?route=pessoas/cicloShow&id=' . $avaliacao['ciclo_id']);
            return;
        }
        if ($avaliacao['status'] === 'pendente') {
            AuditLogger::log('pessoas_avaliacao_iniciada', 'pessoas_avaliacao', $id, ['empresa_id' => $avaliacao['empresa_id'], 'ciclo_id' => $avaliacao['ciclo_id']]);
        }
        $this->redirect('index.php?route=pessoas/avaliacaoResponder&id=' . $id);
    }

    public function avaliacaoResponder(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $avaliacao = $this->avaliacoes->find($id);
        if (!$avaliacao) {
            $_SESSION['flash_error'] = 'Avaliação não encontrada.';
            $this->redirect('index.php?route=pessoas/index');
            return;
        }
        if ($avaliacao['status'] === 'finalizada') {
            $this->redirect('index.php?route=pessoas/avaliacaoResultado&id=' . $id);
            return;
        }
        if ($avaliacao['status'] === 'pendente') {
            $this->redirect('index.php?route=pessoas/cicloShow&id=' . $avaliacao['ciclo_id']);
            return;
        }
        $this->render('pessoas/avaliacoes/responder', [
            'pageTitle' => 'Avaliação de ' . $avaliacao['colaborador_nome'],
            'avaliacao' => $avaliacao,
            'grupos' => $this->avaliacoes->respostasAgrupadas($id),
            'escala' => \App\Core\PessoasAvaliacaoScale::scaleOptions(),
        ]);
    }

    public function avaliacaoSalvar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $avaliacao = $this->avaliacoes->find($id);
        if (!$avaliacao) {
            http_response_code(404);
            echo 'Avaliação não encontrada.';
            return;
        }
        $respostas = (array)($_POST['respostas'] ?? []);
        $observacoes = (array)($_POST['observacoes'] ?? []);
        $payload = [];
        foreach ($respostas as $itemId => $nota) {
            $payload[(int)$itemId] = ['resposta' => $nota, 'observacao' => $observacoes[$itemId] ?? null];
        }
        foreach ($observacoes as $itemId => $obs) {
            if (!isset($payload[(int)$itemId])) {
                $payload[(int)$itemId] = ['resposta' => null, 'observacao' => $obs];
            }
        }
        $ok = $this->avaliacoes->saveAnswers($id, (int)$avaliacao['empresa_id'], $payload);
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível salvar (a avaliação precisa estar em andamento).';
        } else {
            $_SESSION['flash_success'] = 'Progresso salvo.';
        }
        $this->redirect('index.php?route=pessoas/avaliacaoResponder&id=' . $id);
    }

    public function avaliacaoFinalizar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $avaliacao = $this->avaliacoes->find($id);
        if (!$avaliacao) {
            http_response_code(404);
            echo 'Avaliação não encontrada.';
            return;
        }
        // Salva qualquer resposta enviada junto com o clique em Finalizar, antes de validar.
        $respostas = (array)($_POST['respostas'] ?? []);
        $observacoes = (array)($_POST['observacoes'] ?? []);
        $payload = [];
        foreach ($respostas as $itemId => $nota) {
            $payload[(int)$itemId] = ['resposta' => $nota, 'observacao' => $observacoes[$itemId] ?? null];
        }
        $this->avaliacoes->saveAnswers($id, (int)$avaliacao['empresa_id'], $payload);

        $result = $this->avaliacoes->finalizar($id, (int)$avaliacao['empresa_id']);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = 'Não foi possível finalizar: ' . implode(' | ', $result['errors']);
            $this->redirect('index.php?route=pessoas/avaliacaoResponder&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_avaliacao_finalizada', 'pessoas_avaliacao', $id, ['empresa_id' => $avaliacao['empresa_id'], 'ciclo_id' => $avaliacao['ciclo_id']]);
        $_SESSION['flash_success'] = 'Avaliação finalizada.';
        $this->redirect('index.php?route=pessoas/avaliacaoResultado&id=' . $id);
    }

    public function avaliacaoResultado(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $avaliacao = $this->avaliacoes->find($id);
        if (!$avaliacao || $avaliacao['status'] !== 'finalizada') {
            $_SESSION['flash_error'] = 'Resultado indisponível (a avaliação ainda não foi finalizada).';
            $this->redirect('index.php?route=pessoas/index');
            return;
        }
        // Sprint 02: mapa resposta_id -> gap_id (já registrado) para a View
        // decidir entre mostrar "Registrar GAP" ou "GAP registrado" por item,
        // sem repetir uma consulta por linha.
        $gapPorResposta = [];
        foreach ($this->avaliacoes->listRespostas($id) as $item) {
            $gapExistente = $this->gaps->findByResposta((int)$item['id']);
            if ($gapExistente !== null) {
                $gapPorResposta[(int)$item['id']] = (int)$gapExistente['id'];
            }
        }
        $this->render('pessoas/avaliacoes/resultado', [
            'pageTitle' => 'Resultado — ' . $avaliacao['colaborador_nome'],
            'avaliacao' => $avaliacao,
            'grupos' => $this->avaliacoes->respostasAgrupadas($id),
            'resultadoPorGrupo' => $this->avaliacoes->resultadoPorGrupo($id),
            'classificacaoGeral' => \App\Core\PessoasAvaliacaoScale::classification((float)$avaliacao['resultado']),
            'gapPorResposta' => $gapPorResposta,
        ]);
    }
}

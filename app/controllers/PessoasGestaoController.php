<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\ColaboradorModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Core\AccessControl;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaDesenvolvimentoModel;
use App\Models\PessoaFeedbackModel;
use App\Models\PessoaGapModel;
use App\Models\PlanoAcaoTaskModel;
use App\Models\TreinamentoModel;

/**
 * Pilar de Pessoas, Sprint 02 - Resultado -> GAP -> Feedback -> Ação de
 * Melhoria + Histórico do Colaborador. Acesso restrito a Instituto/Cliente
 * Admin (requireClienteAdminAccess()), tenant sempre validado no Model.
 */
class PessoasGestaoController extends BaseController
{
    private ColaboradorModel $colaboradores;
    private PessoaAvaliacaoModel $avaliacoes;
    private PessoaGapModel $gaps;
    private PessoaFeedbackModel $feedbacks;
    private PessoaAcaoMelhoriaModel $acoes;
    private PessoaDesenvolvimentoModel $desenvolvimento;

    public function __construct()
    {
        $this->colaboradores = new ColaboradorModel();
        $this->avaliacoes = new PessoaAvaliacaoModel();
        $this->gaps = new PessoaGapModel();
        $this->feedbacks = new PessoaFeedbackModel();
        $this->acoes = new PessoaAcaoMelhoriaModel();
        $this->desenvolvimento = new PessoaDesenvolvimentoModel();
    }

    /** Resolve o colaborador garantindo tenant (find() já filtra por escopo; canAccessCliente() é defesa redundante). */
    private function colaboradorSeguro(int $colaboradorId): ?array
    {
        $colaborador = $this->colaboradores->find($colaboradorId);
        if (!$colaborador || !$this->canAccessCliente((int)$colaborador['cliente_id'])) {
            return null;
        }
        return $colaborador;
    }

    public function colaboradorHistorico(): void
    {
        $this->requireClienteAdminAccess();
        $colaboradorId = (int)($_GET['id'] ?? 0);
        $colaborador = $this->colaboradorSeguro($colaboradorId);
        if (!$colaborador) {
            $_SESSION['flash_error'] = 'Colaborador não encontrado.';
            $this->redirect('index.php?route=colaboradores/index');
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];

        $avaliacoesDoColaborador = $this->db_findAvaliacoesByColaborador($colaboradorId, $empresaId);

        $gaps = $this->gaps->listByColaborador($colaboradorId, $empresaId);
        $acoes = $this->acoes->listByColaborador($colaboradorId, $empresaId);
        // Sprint 03: desenvolvimento (Plano/Treinamento/Necessidade + estado derivado)
        // por Acao, e mapas para a leitura encadeada Avaliacao -> GAP -> Acao -> Desenvolvimento.
        $desenvolvimento = [];
        foreach ($acoes as $a) {
            $desenvolvimento[(int)$a['id']] = $this->desenvolvimento->desenvolvimentoDaAcao($a);
        }
        $gapsPorId = [];
        foreach ($gaps as $g) {
            $gapsPorId[(int)$g['id']] = $g;
        }
        $this->render('pessoas/historico/colaborador', [
            'pageTitle' => 'Histórico de Desenvolvimento — ' . $colaborador['nome'],
            'colaborador' => $colaborador,
            'avaliacoes' => $avaliacoesDoColaborador,
            'gaps' => $gaps,
            'gapsPorId' => $gapsPorId,
            'feedbacks' => $this->feedbacks->listByColaborador($colaboradorId, $empresaId),
            'acoes' => $acoes,
            'desenvolvimento' => $desenvolvimento,
            'usuariosResponsaveis' => $this->acoes->usuariosResponsaveisDisponiveis($empresaId),
            'links' => $this->linksPermitidos(),
        ]);
    }

    /** Pequena consulta direta (não há caso de uso para um Model dedicado só a "avaliações de um colaborador" fora do ciclo). */
    private function db_findAvaliacoesByColaborador(int $colaboradorId, int $empresaId): array
    {
        $pdo = \App\Database\Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT a.id, a.status, a.resultado, a.finalizado_em, c.nome AS ciclo_nome, m.nome AS modelo_nome
             FROM pessoas_avaliacoes a
             JOIN pessoas_ciclos_avaliacao c ON c.id = a.ciclo_id
             JOIN pessoas_modelos_avaliacao m ON m.id = c.modelo_id
             WHERE a.colaborador_id = :cid AND a.empresa_id = :eid
             ORDER BY a.created_at DESC, a.id DESC"
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------------
    // GAP
    // ---------------------------------------------------------------

    public function gapCreate(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
        $colaborador = $this->colaboradorSeguro($colaboradorId);
        if (!$colaborador) {
            http_response_code(404);
            echo 'Colaborador não encontrado.';
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];
        $avaliacaoId = (int)($_POST['avaliacao_id'] ?? 0);
        $respostaId = (int)($_POST['resposta_id'] ?? 0);
        $createdBy = (int)($_SESSION['user']['id'] ?? 0);
        $payload = ['titulo' => $_POST['titulo'] ?? '', 'descricao' => $_POST['descricao'] ?? null, 'prioridade' => $_POST['prioridade'] ?? 'media'];

        if ($avaliacaoId > 0 && $respostaId > 0) {
            $result = $this->gaps->createFromResposta($empresaId, $colaboradorId, $avaliacaoId, $respostaId, $payload, $this->avaliacoes, $createdBy);
            $gapId = $result['id'];
            if ($gapId <= 0) {
                $_SESSION['flash_error'] = 'Não foi possível registrar o GAP (verifique se a resposta pertence à avaliação informada).';
                $this->redirect('index.php?route=pessoas/avaliacaoResultado&id=' . $avaliacaoId);
                return;
            }
            if (!$result['already_existed']) {
                AuditLogger::log('pessoas_gap_criado', 'pessoas_gap', $gapId, ['empresa_id' => $empresaId, 'colaborador_id' => $colaboradorId, 'origem' => 'avaliacao']);
                $_SESSION['flash_success'] = 'GAP registrado.';
            } else {
                $_SESSION['flash_success'] = 'Esta resposta já tinha um GAP registrado.';
            }
            $this->redirect('index.php?route=pessoas/gapShow&id=' . $gapId);
            return;
        }

        $gapId = $this->gaps->createManual($empresaId, $colaboradorId, $payload, $this->colaboradores, $createdBy);
        if ($gapId <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível registrar o GAP. Verifique o título.';
            $this->redirect('index.php?route=pessoas/colaboradorHistorico&id=' . $colaboradorId);
            return;
        }
        AuditLogger::log('pessoas_gap_criado', 'pessoas_gap', $gapId, ['empresa_id' => $empresaId, 'colaborador_id' => $colaboradorId, 'origem' => 'manual']);
        $_SESSION['flash_success'] = 'GAP registrado.';
        $this->redirect('index.php?route=pessoas/gapShow&id=' . $gapId);
    }

    public function gapShow(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $gap = $this->gaps->find($id);
        if (!$gap) {
            $_SESSION['flash_error'] = 'GAP não encontrado.';
            $this->redirect('index.php?route=colaboradores/index');
            return;
        }
        $colaborador = $this->colaboradorSeguro((int)$gap['colaborador_id']);
        if (!$colaborador) {
            http_response_code(404);
            echo 'Colaborador não encontrado.';
            return;
        }
        $this->render('pessoas/gaps/show', [
            'pageTitle' => 'GAP — ' . $gap['titulo'],
            'gap' => $gap,
            'colaborador' => $colaborador,
            'feedbacks' => $this->feedbacksRelacionadosAoGap((int)$gap['id']),
            'acoes' => $this->acoes->listByGap((int)$gap['id']),
            'usuariosResponsaveis' => $this->acoes->usuariosResponsaveisDisponiveis((int)$gap['empresa_id']),
        ]);
    }

    private function feedbacksRelacionadosAoGap(int $gapId): array
    {
        $pdo = \App\Database\Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pessoas_feedbacks WHERE gap_id = :gid ORDER BY data_feedback DESC, id DESC');
        $stmt->execute(['gid' => $gapId]);
        return $stmt->fetchAll();
    }

    public function gapStatus(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $gap = $this->gaps->find($id);
        if (!$gap) {
            http_response_code(404);
            echo 'GAP não encontrado.';
            return;
        }
        $novoStatus = (string)($_POST['status'] ?? '');
        if (!$this->gaps->updateStatus($id, (int)$gap['empresa_id'], $novoStatus)) {
            $_SESSION['flash_error'] = 'Transição de status inválida.';
            $this->redirect('index.php?route=pessoas/gapShow&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_gap_status_alterado', 'pessoas_gap', $id, ['empresa_id' => $gap['empresa_id'], 'de' => $gap['status'], 'para' => $novoStatus]);
        $_SESSION['flash_success'] = 'Status do GAP atualizado.';
        $this->redirect('index.php?route=pessoas/gapShow&id=' . $id);
    }

    // ---------------------------------------------------------------
    // Feedback
    // ---------------------------------------------------------------

    public function feedbackCreate(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
        $colaborador = $this->colaboradorSeguro($colaboradorId);
        if (!$colaborador) {
            http_response_code(404);
            echo 'Colaborador não encontrado.';
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];
        $registradoPor = (int)($_SESSION['user']['id'] ?? 0);
        $id = $this->feedbacks->create($empresaId, $colaboradorId, [
            'tipo' => $_POST['tipo'] ?? '',
            'titulo' => $_POST['titulo'] ?? '',
            'descricao' => $_POST['descricao'] ?? '',
            'data_feedback' => $_POST['data_feedback'] ?? '',
            'avaliacao_id' => $_POST['avaliacao_id'] ?? 0,
            'gap_id' => $_POST['gap_id'] ?? 0,
        ], $this->colaboradores, $this->avaliacoes, $this->gaps, $registradoPor);

        $voltarPara = (string)($_POST['voltar_para'] ?? '');
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível registrar o feedback. Verifique tipo, título e descrição.';
        } else {
            AuditLogger::log('pessoas_feedback_criado', 'pessoas_feedback', $id, ['empresa_id' => $empresaId, 'colaborador_id' => $colaboradorId]);
            $_SESSION['flash_success'] = 'Feedback registrado.';
        }
        $this->redirect($voltarPara !== '' ? $voltarPara : 'index.php?route=pessoas/colaboradorHistorico&id=' . $colaboradorId);
    }

    // ---------------------------------------------------------------
    // Ação de Melhoria
    // ---------------------------------------------------------------

    public function acaoCreate(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
        $colaborador = $this->colaboradorSeguro($colaboradorId);
        if (!$colaborador) {
            http_response_code(404);
            echo 'Colaborador não encontrado.';
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];
        $createdBy = (int)($_SESSION['user']['id'] ?? 0);
        $gapId = (int)($_POST['gap_id'] ?? 0);
        $id = $this->acoes->create($empresaId, $colaboradorId, [
            'titulo' => $_POST['titulo'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'responsavel_usuario_id' => $_POST['responsavel_usuario_id'] ?? 0,
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'prazo' => $_POST['prazo'] ?? null,
            'gap_id' => $gapId,
            'avaliacao_id' => $_POST['avaliacao_id'] ?? 0,
        ], $this->colaboradores, $this->gaps, $this->avaliacoes, $createdBy);

        $voltarPara = $gapId > 0 ? 'index.php?route=pessoas/gapShow&id=' . $gapId : 'index.php?route=pessoas/colaboradorHistorico&id=' . $colaboradorId;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível criar a Ação de Melhoria. Verifique o título.';
        } else {
            AuditLogger::log('pessoas_acao_melhoria_criada', 'pessoas_acao_melhoria', $id, ['empresa_id' => $empresaId, 'colaborador_id' => $colaboradorId, 'gap_id' => $gapId ?: null]);
            $_SESSION['flash_success'] = 'Ação de Melhoria criada.';
        }
        $this->redirect($voltarPara);
    }

    public function acaoEmAndamento(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        [$acao, $voltarPara] = $this->acaoContexto();
        if ($acao === null) {
            return;
        }
        if ($this->acoes->marcarEmAndamento((int)$acao['id'], (int)$acao['empresa_id'])) {
            AuditLogger::log('pessoas_acao_melhoria_status_alterado', 'pessoas_acao_melhoria', $acao['id'], ['empresa_id' => $acao['empresa_id'], 'para' => 'em_andamento']);
            $_SESSION['flash_success'] = 'Ação marcada como em andamento.';
        } else {
            $_SESSION['flash_error'] = 'Não foi possível atualizar a ação.';
        }
        $this->redirect($voltarPara);
    }

    public function acaoConcluir(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        [$acao, $voltarPara] = $this->acaoContexto();
        if ($acao === null) {
            return;
        }
        if ($this->acoes->concluir((int)$acao['id'], (int)$acao['empresa_id'], (string)($_POST['conclusao'] ?? ''))) {
            AuditLogger::log('pessoas_acao_melhoria_status_alterado', 'pessoas_acao_melhoria', $acao['id'], ['empresa_id' => $acao['empresa_id'], 'para' => 'concluida']);
            $_SESSION['flash_success'] = 'Ação concluída.';
        } else {
            $_SESSION['flash_error'] = 'Não foi possível concluir a ação.';
        }
        $this->redirect($voltarPara);
    }

    public function acaoCancelar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        [$acao, $voltarPara] = $this->acaoContexto();
        if ($acao === null) {
            return;
        }
        if ($this->acoes->cancelar((int)$acao['id'], (int)$acao['empresa_id'])) {
            AuditLogger::log('pessoas_acao_melhoria_status_alterado', 'pessoas_acao_melhoria', $acao['id'], ['empresa_id' => $acao['empresa_id'], 'para' => 'cancelada']);
            $_SESSION['flash_success'] = 'Ação cancelada.';
        } else {
            $_SESSION['flash_error'] = 'Não foi possível cancelar a ação.';
        }
        $this->redirect($voltarPara);
    }

    // ---------------------------------------------------------------
    // Sprint 03 - Desenvolvimento (Plano de Ação / Treinamento / Necessidade)
    // ---------------------------------------------------------------

    /**
     * Links operacionais para módulos de destino só aparecem se o usuário JÁ tem
     * permissão nesses módulos - acesso a Pessoas não concede nada além disso.
     */
    private function linksPermitidos(): array
    {
        $user = $_SESSION['user'] ?? null;
        return [
            'plano' => AccessControl::canAccessRoute('planoacao/show', 'GET', $user),
            'treinamento' => AccessControl::canAccessRoute('treinamentos/show', 'GET', $user),
        ];
    }

    /** Bloqueia a operação se o usuário não puder escrever no módulo de destino (sem bypass de RBAC). */
    private function exigirModuloDestino(string $route): bool
    {
        if (AccessControl::canAccessRoute($route, 'POST', $_SESSION['user'] ?? null)) {
            return true;
        }
        $_SESSION['flash_error'] = 'Operação não permitida.';
        return false;
    }

    public function acaoShow(): void
    {
        $this->requireClienteAdminAccess();
        $acao = $this->acoes->find((int)($_GET['id'] ?? 0));
        if (!$acao || !$this->canAccessCliente((int)$acao['empresa_id'])) {
            $_SESSION['flash_error'] = 'Ação de Melhoria não encontrada.';
            $this->redirect('index.php?route=colaboradores/index');
            return;
        }
        $gap = !empty($acao['gap_id']) ? $this->gaps->find((int)$acao['gap_id']) : null;
        $this->render('pessoas/acoes/show', [
            'pageTitle' => 'Ação de Melhoria — ' . $acao['titulo'],
            'acao' => $acao,
            'gap' => $gap,
            'dev' => $this->desenvolvimento->desenvolvimentoDaAcao($acao),
            'treinamentosDisponiveis' => $this->desenvolvimento->treinamentosDisponiveis((int)$acao['empresa_id']),
            'links' => $this->linksPermitidos(),
        ]);
    }

    private function acaoParaDesenvolvimento(): ?array
    {
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return null;
        }
        $acao = $this->acoes->find((int)($_POST['acao_id'] ?? 0));
        if (!$acao || !$this->canAccessCliente((int)$acao['empresa_id'])) {
            http_response_code(404);
            echo 'Ação de Melhoria não encontrada.';
            return null;
        }
        return $acao;
    }

    public function acaoEncaminharPlano(): void
    {
        $this->requireClienteAdminAccess();
        $acao = $this->acaoParaDesenvolvimento();
        if ($acao === null) {
            return;
        }
        $voltar = 'index.php?route=pessoas/acaoShow&id=' . (int)$acao['id'];
        if (!$this->exigirModuloDestino('planoacao/store')) {
            $this->redirect($voltar);
            return;
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $r = $this->desenvolvimento->encaminharPlanoAcao((int)$acao['id'], [
            'titulo' => $_POST['titulo'] ?? '',
            'descricao' => $_POST['descricao'] ?? '',
            'prazo' => $_POST['prazo'] ?? null,
        ], $this->acoes, new PlanoAcaoTaskModel(), $userId);
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } elseif ($r['already_existed']) {
            $_SESSION['flash_success'] = 'Este encaminhamento já existe.';
        } else {
            AuditLogger::log('pessoas_acao_encaminhada_plano_acao', 'pessoas_acao_melhoria', (int)$acao['id'], ['empresa_id' => $acao['empresa_id'], 'plano_id' => $r['plano_id']]);
            $_SESSION['flash_success'] = 'Plano de Ação criado com sucesso.';
        }
        $this->redirect($voltar);
    }

    public function acaoEncaminharTreinamento(): void
    {
        $this->requireClienteAdminAccess();
        $acao = $this->acaoParaDesenvolvimento();
        if ($acao === null) {
            return;
        }
        $voltar = 'index.php?route=pessoas/acaoShow&id=' . (int)$acao['id'];
        if (!$this->exigirModuloDestino('treinamentos/add_participante_extra')) {
            $this->redirect($voltar);
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $r = $this->desenvolvimento->vincularTreinamento((int)$acao['id'], $treinamentoId, $this->acoes, new TreinamentoModel(), (int)($_SESSION['user']['id'] ?? 0));
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } elseif ($r['already_existed']) {
            $_SESSION['flash_success'] = 'Este encaminhamento já existe.';
        } else {
            AuditLogger::log('pessoas_acao_encaminhada_treinamento', 'pessoas_acao_melhoria', (int)$acao['id'], ['empresa_id' => $acao['empresa_id'], 'treinamento_id' => $treinamentoId]);
            $_SESSION['flash_success'] = 'Treinamento vinculado.';
        }
        $this->redirect($voltar);
    }

    public function necessidadeCreate(): void
    {
        $this->requireClienteAdminAccess();
        $acao = $this->acaoParaDesenvolvimento();
        if ($acao === null) {
            return;
        }
        $r = $this->desenvolvimento->registrarNecessidade((int)$acao['id'], [
            'titulo' => $_POST['titulo'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'prioridade' => $_POST['prioridade'] ?? 'media',
        ], $this->acoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } elseif ($r['already_existed']) {
            $_SESSION['flash_success'] = 'Este encaminhamento já existe.';
        } else {
            AuditLogger::log('pessoas_necessidade_treinamento_criada', 'pessoas_necessidade_treinamento', $r['id'], ['empresa_id' => $acao['empresa_id'], 'acao_id' => $acao['id']]);
            $_SESSION['flash_success'] = 'Necessidade de treinamento registrada.';
        }
        $this->redirect('index.php?route=pessoas/acaoShow&id=' . (int)$acao['id']);
    }

    public function necessidadeAtender(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $n = $this->desenvolvimento->findNecessidade((int)($_POST['id'] ?? 0));
        if (!$n || !$this->canAccessCliente((int)$n['empresa_id'])) {
            http_response_code(404);
            echo 'Necessidade não encontrada.';
            return;
        }
        $voltar = 'index.php?route=pessoas/acaoShow&id=' . (int)$n['acao_melhoria_id'];
        if (!$this->exigirModuloDestino('treinamentos/add_participante_extra')) {
            $this->redirect($voltar);
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $r = $this->desenvolvimento->atenderNecessidade((int)$n['id'], $treinamentoId, $this->acoes, new TreinamentoModel(), (int)($_SESSION['user']['id'] ?? 0));
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } else {
            AuditLogger::log('pessoas_necessidade_treinamento_atendida', 'pessoas_necessidade_treinamento', (int)$n['id'], ['empresa_id' => $n['empresa_id'], 'treinamento_id' => $treinamentoId]);
            $_SESSION['flash_success'] = 'Necessidade atendida e treinamento vinculado.';
        }
        $this->redirect($voltar);
    }

    public function necessidadeCancelar(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $n = $this->desenvolvimento->findNecessidade((int)($_POST['id'] ?? 0));
        if (!$n || !$this->canAccessCliente((int)$n['empresa_id'])) {
            http_response_code(404);
            echo 'Necessidade não encontrada.';
            return;
        }
        if ($this->desenvolvimento->cancelarNecessidade((int)$n['id'])) {
            AuditLogger::log('pessoas_necessidade_treinamento_cancelada', 'pessoas_necessidade_treinamento', (int)$n['id'], ['empresa_id' => $n['empresa_id']]);
            $_SESSION['flash_success'] = 'Necessidade cancelada.';
        } else {
            $_SESSION['flash_error'] = 'Operação não permitida.';
        }
        $this->redirect('index.php?route=pessoas/acaoShow&id=' . (int)$n['acao_melhoria_id']);
    }

    /** @return array{0:?array,1:string} */
    private function acaoContexto(): array
    {
        $id = (int)($_POST['id'] ?? 0);
        $acao = $this->acoes->find($id);
        if (!$acao) {
            http_response_code(404);
            echo 'Ação não encontrada.';
            return [null, ''];
        }
        $voltarPara = !empty($acao['gap_id'])
            ? 'index.php?route=pessoas/gapShow&id=' . (int)$acao['gap_id']
            : 'index.php?route=pessoas/colaboradorHistorico&id=' . (int)$acao['colaborador_id'];
        return [$acao, $voltarPara];
    }
}

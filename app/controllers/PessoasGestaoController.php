<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\ColaboradorModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaFeedbackModel;
use App\Models\PessoaGapModel;

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

    public function __construct()
    {
        $this->colaboradores = new ColaboradorModel();
        $this->avaliacoes = new PessoaAvaliacaoModel();
        $this->gaps = new PessoaGapModel();
        $this->feedbacks = new PessoaFeedbackModel();
        $this->acoes = new PessoaAcaoMelhoriaModel();
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

        $this->render('pessoas/historico/colaborador', [
            'pageTitle' => 'Histórico de Desenvolvimento — ' . $colaborador['nome'],
            'colaborador' => $colaborador,
            'avaliacoes' => $avaliacoesDoColaborador,
            'gaps' => $this->gaps->listByColaborador($colaboradorId, $empresaId),
            'feedbacks' => $this->feedbacks->listByColaborador($colaboradorId, $empresaId),
            'acoes' => $this->acoes->listByColaborador($colaboradorId, $empresaId),
            'usuariosResponsaveis' => $this->acoes->usuariosResponsaveisDisponiveis($empresaId),
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

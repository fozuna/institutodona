<?php
namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaDesenvolvimentoModel;
use App\Models\PessoaGapModel;
use App\Models\PessoaPdiModel;

/**
 * Pilar de Pessoas, Sprint 04 - PDI. Acesso restrito a Instituto/Cliente Admin
 * (requireClienteAdminAccess). Ordem em toda escrita: auth -> CSRF -> tenant/
 * propriedade (Model.find já escopa + canAccessCliente) -> relação semântica ->
 * operação. PDI nunca sincroniza status de Ação/GAP/Plano/Treinamento.
 */
class PessoasPdiController extends BaseController
{
    private const PER_PAGE = 15;

    private PessoaPdiModel $pdis;
    private ColaboradorModel $colaboradores;
    private PessoaGapModel $gaps;
    private PessoaAcaoMelhoriaModel $acoes;
    private PessoaAvaliacaoModel $avaliacoes;
    private PessoaDesenvolvimentoModel $desenvolvimento;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->pdis = new PessoaPdiModel();
        $this->colaboradores = new ColaboradorModel();
        $this->gaps = new PessoaGapModel();
        $this->acoes = new PessoaAcaoMelhoriaModel();
        $this->avaliacoes = new PessoaAvaliacaoModel();
        $this->desenvolvimento = new PessoaDesenvolvimentoModel();
        $this->clientes = new ClienteModel();
    }

    private function userId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    /** Guarda de escrita: auth/RBAC + CSRF. Retorna false (e responde 400) se o CSRF falhar. */
    private function guardPost(): bool
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return false;
        }
        return true;
    }

    private function notFound(string $msg = 'Recurso não encontrado.'): void
    {
        http_response_code(404);
        echo $msg;
    }

    private function colaboradorSeguro(int $id): ?array
    {
        $c = $this->colaboradores->find($id);
        return ($c && $this->canAccessCliente((int)$c['cliente_id'])) ? $c : null;
    }

    private function pdiSeguro(int $id): ?array
    {
        $p = $id > 0 ? $this->pdis->find($id) : null;
        return ($p && $this->canAccessCliente((int)$p['empresa_id'])) ? $p : null;
    }

    private function back(int $pdiId): void
    {
        $this->redirect('index.php?route=pessoas/pdiShow&id=' . $pdiId);
    }

    private function linksPermitidos(): array
    {
        $user = $_SESSION['user'] ?? null;
        return [
            'plano' => AccessControl::canAccessRoute('planoacao/show', 'GET', $user),
            'treinamento' => AccessControl::canAccessRoute('treinamentos/show', 'GET', $user),
        ];
    }

    // ---------------------------------------------------------------
    // Listagem
    // ---------------------------------------------------------------

    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $clientes = $this->clientes->all();
        $acessiveis = array_map(static fn(array $c): int => (int)$c['id'], $clientes);
        $empresaReq = (int)($_GET['empresa_id'] ?? 0);
        $empresaIds = ($empresaReq > 0 && in_array($empresaReq, $acessiveis, true)) ? [$empresaReq] : $acessiveis;

        $status = (string)($_GET['status'] ?? '');
        $inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['inicio'] ?? '')) ? (string)$_GET['inicio'] : '';
        $fim = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['fim'] ?? '')) ? (string)$_GET['fim'] : '';
        $filters = ['status' => $status, 'q' => trim((string)($_GET['q'] ?? '')), 'inicio' => $inicio, 'fim' => $fim];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->pdis->paginate($empresaIds, $filters, $page, self::PER_PAGE);

        $this->render('pessoas/pdi/index', [
            'pageTitle' => 'PDI — Plano de Desenvolvimento Individual',
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'clientes' => $clientes,
            'selectedEmpresa' => ($empresaReq > 0 && in_array($empresaReq, $acessiveis, true)) ? $empresaReq : 0,
        ]);
    }

    // ---------------------------------------------------------------
    // Criação
    // ---------------------------------------------------------------

    /** Formulário (GET): nunca cria PDI sozinho. gap_id opcional pré-seleciona o GAP. */
    public function create(): void
    {
        $this->requireClienteAdminAccess();
        $colaborador = $this->colaboradorSeguro((int)($_GET['colaborador_id'] ?? 0));
        if (!$colaborador) {
            $_SESSION['flash_error'] = 'Colaborador não encontrado.';
            $this->redirect('index.php?route=colaboradores/index');
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];
        $gapId = (int)($_GET['gap_id'] ?? 0);
        if ($gapId > 0 && $this->gaps->findForColaborador($gapId, (int)$colaborador['id'], $empresaId) === null) {
            $gapId = 0;
        }
        $this->render('pessoas/pdi/create', [
            'pageTitle' => 'Novo PDI — ' . $colaborador['nome'],
            'colaborador' => $colaborador,
            'gapId' => $gapId,
            'gapsDisponiveis' => array_values(array_filter(
                $this->gaps->listByColaborador((int)$colaborador['id'], $empresaId),
                static fn(array $g): bool => $g['status'] !== 'resolvido'
            )),
            'tituloSugerido' => 'PDI — ' . $colaborador['nome'] . ' — ' . date('Y'),
            'pdiAtivo' => $this->pdis->pdiAtivoDoColaborador((int)$colaborador['id'], $empresaId),
        ]);
    }

    public function store(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
        $colaborador = $this->colaboradorSeguro($colaboradorId);
        if (!$colaborador) {
            $this->notFound('Colaborador não encontrado.');
            return;
        }
        $empresaId = (int)$colaborador['cliente_id'];
        $id = $this->pdis->create($empresaId, $colaboradorId, [
            'titulo' => $_POST['titulo'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_fim_prevista' => $_POST['data_fim_prevista'] ?? null,
        ], $this->colaboradores, $this->userId());
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível criar o PDI. Verifique título e datas.';
            $this->redirect('index.php?route=pessoas/pdiCreate&colaborador_id=' . $colaboradorId);
            return;
        }
        AuditLogger::log('pessoas_pdi_criado', 'pessoas_pdi', $id, ['empresa_id' => $empresaId, 'colaborador_id' => $colaboradorId]);
        foreach ((array)($_POST['gap_ids'] ?? []) as $gid) {
            $r = $this->pdis->addGap($id, (int)$gid, $this->gaps);
            if ($r['ok'] && !$r['already_existed']) {
                AuditLogger::log('pessoas_pdi_gap_vinculado', 'pessoas_pdi', $id, ['empresa_id' => $empresaId, 'gap_id' => (int)$gid]);
            }
        }
        $_SESSION['flash_success'] = 'PDI criado em rascunho.';
        $this->back($id);
    }

    // ---------------------------------------------------------------
    // Detalhe
    // ---------------------------------------------------------------

    public function show(): void
    {
        $this->requireClienteAdminAccess();
        $pdi = $this->pdiSeguro((int)($_GET['id'] ?? 0));
        if (!$pdi) {
            $_SESSION['flash_error'] = 'PDI não encontrado.';
            $this->redirect('index.php?route=pessoas/pdiIndex');
            return;
        }
        $colaborador = $this->colaboradorSeguro((int)$pdi['colaborador_id']);
        if (!$colaborador) {
            $this->notFound('Colaborador não encontrado.');
            return;
        }
        $objetivos = $this->pdis->objetivosDoPdi((int)$pdi['id']);
        foreach ($objetivos as &$o) {
            $o['acoes'] = $this->pdis->acoesDoObjetivo((int)$o['id']);
            $o['disponiveis'] = in_array($pdi['status'], ['rascunho', 'ativo'], true)
                ? $this->pdis->acoesDisponiveis((int)$o['id'], $this->acoes) : [];
            $o['desenvolvimento'] = [];
            foreach ($o['acoes'] as $a) {
                $o['desenvolvimento'][(int)$a['id']] = $this->desenvolvimento->desenvolvimentoDaAcao($a);
            }
        }
        unset($o);
        $gapsDoPdi = $this->pdis->gapsDoPdi((int)$pdi['id']);
        $idsNoPdi = array_map(static fn($g) => (int)$g['id'], $gapsDoPdi);
        $this->render('pessoas/pdi/show', [
            'pageTitle' => 'PDI — ' . $pdi['titulo'],
            'pdi' => $pdi,
            'colaborador' => $colaborador,
            'gapsDoPdi' => $gapsDoPdi,
            'gapsDisponiveis' => array_values(array_filter(
                $this->gaps->listByColaborador((int)$colaborador['id'], (int)$pdi['empresa_id']),
                static fn(array $g): bool => !in_array((int)$g['id'], $idsNoPdi, true)
            )),
            'objetivos' => $objetivos,
            'feedbacks' => $this->pdis->feedbacksNoPeriodo($pdi),
            'usuariosResponsaveis' => $this->acoes->usuariosResponsaveisDisponiveis((int)$pdi['empresa_id']),
            'links' => $this->linksPermitidos(),
        ]);
    }

    // ---------------------------------------------------------------
    // Ciclo de vida
    // ---------------------------------------------------------------

    public function update(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        if (!$this->pdis->update((int)$pdi['id'], $_POST)) {
            $_SESSION['flash_error'] = 'Só é possível editar um PDI em rascunho, com título e datas válidos.';
        } else {
            AuditLogger::log('pessoas_pdi_alterado', 'pessoas_pdi', (int)$pdi['id'], ['empresa_id' => $pdi['empresa_id']]);
            $_SESSION['flash_success'] = 'PDI atualizado.';
        }
        $this->back((int)$pdi['id']);
    }

    public function ativar(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        $r = $this->pdis->ativar((int)$pdi['id']);
        if ($r['ok']) {
            AuditLogger::log('pessoas_pdi_ativado', 'pessoas_pdi', (int)$pdi['id'], ['empresa_id' => $pdi['empresa_id'], 'colaborador_id' => $pdi['colaborador_id']]);
            $_SESSION['flash_success'] = 'PDI ativado.';
        } else {
            $_SESSION['flash_error'] = $r['error'];
        }
        $this->back((int)$pdi['id']);
    }

    public function concluir(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        $r = $this->pdis->concluir((int)$pdi['id'], (string)($_POST['observacoes_conclusao'] ?? ''));
        if ($r['ok']) {
            AuditLogger::log('pessoas_pdi_concluido', 'pessoas_pdi', (int)$pdi['id'], ['empresa_id' => $pdi['empresa_id'], 'colaborador_id' => $pdi['colaborador_id']]);
            $_SESSION['flash_success'] = 'PDI concluído.';
        } else {
            $_SESSION['flash_error'] = $r['error'];
        }
        $this->back((int)$pdi['id']);
    }

    public function cancelar(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        if ($this->pdis->cancelar((int)$pdi['id'])) {
            AuditLogger::log('pessoas_pdi_cancelado', 'pessoas_pdi', (int)$pdi['id'], ['empresa_id' => $pdi['empresa_id'], 'de' => $pdi['status']]);
            $_SESSION['flash_success'] = 'PDI cancelado. GAPs, Ações, Planos e Treinamentos foram preservados.';
        } else {
            $_SESSION['flash_error'] = 'Só é possível cancelar um PDI em rascunho ou ativo.';
        }
        $this->back((int)$pdi['id']);
    }

    // ---------------------------------------------------------------
    // GAPs
    // ---------------------------------------------------------------

    /** Aceita retorno opcional para a tela do GAP (return=gap). */
    public function gapAdd(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['pdi_id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        $gapId = (int)($_POST['gap_id'] ?? 0);
        $r = $this->pdis->addGap((int)$pdi['id'], $gapId, $this->gaps);
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } elseif ($r['already_existed']) {
            $_SESSION['flash_success'] = 'Este GAP já faz parte do PDI.';
        } else {
            AuditLogger::log('pessoas_pdi_gap_vinculado', 'pessoas_pdi', (int)$pdi['id'], ['empresa_id' => $pdi['empresa_id'], 'gap_id' => $gapId]);
            $_SESSION['flash_success'] = 'GAP adicionado ao PDI.';
        }
        if (($_POST['return'] ?? '') === 'gap' && $gapId > 0) {
            $this->redirect('index.php?route=pessoas/gapShow&id=' . $gapId);
            return;
        }
        $this->back((int)$pdi['id']);
    }

    public function gapRemove(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['pdi_id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        if ($this->pdis->removeGap((int)$pdi['id'], (int)($_POST['gap_id'] ?? 0))) {
            $_SESSION['flash_success'] = 'GAP removido do PDI (o GAP em si foi preservado).';
        } else {
            $_SESSION['flash_error'] = 'Só é possível remover GAP de um PDI em rascunho.';
        }
        $this->back((int)$pdi['id']);
    }

    // ---------------------------------------------------------------
    // Objetivos
    // ---------------------------------------------------------------

    public function objetivoCreate(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $pdi = $this->pdiSeguro((int)($_POST['pdi_id'] ?? 0));
        if (!$pdi) {
            $this->notFound('PDI não encontrado.');
            return;
        }
        $id = $this->pdis->addObjetivo((int)$pdi['id'], $_POST);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível criar o objetivo (PDI concluído/cancelado ou título vazio).';
        } else {
            AuditLogger::log('pessoas_pdi_objetivo_criado', 'pessoas_pdi_objetivo', $id, ['empresa_id' => $pdi['empresa_id'], 'pdi_id' => $pdi['id']]);
            $_SESSION['flash_success'] = 'Objetivo criado.';
        }
        $this->back((int)$pdi['id']);
    }

    public function objetivoStatus(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $o = $this->pdis->findObjetivo((int)($_POST['objetivo_id'] ?? 0));
        if (!$o || !$this->canAccessCliente((int)$o['empresa_id'])) {
            $this->notFound('Objetivo não encontrado.');
            return;
        }
        $novo = (string)($_POST['status'] ?? '');
        if ($this->pdis->setObjetivoStatus((int)$o['id'], $novo)) {
            AuditLogger::log('pessoas_pdi_objetivo_status_alterado', 'pessoas_pdi_objetivo', (int)$o['id'], ['empresa_id' => $o['empresa_id'], 'pdi_id' => $o['pdi_id'], 'de' => $o['status'], 'para' => $novo]);
            $_SESSION['flash_success'] = 'Status do objetivo atualizado.';
        } else {
            $_SESSION['flash_error'] = 'Transição de status do objetivo não permitida.';
        }
        $this->back((int)$o['pdi_id']);
    }

    // ---------------------------------------------------------------
    // Ações do objetivo
    // ---------------------------------------------------------------

    public function acaoVincular(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $o = $this->pdis->findObjetivo((int)($_POST['objetivo_id'] ?? 0));
        if (!$o || !$this->canAccessCliente((int)$o['empresa_id'])) {
            $this->notFound('Objetivo não encontrado.');
            return;
        }
        $r = $this->pdis->vincularAcao((int)$o['id'], (int)($_POST['acao_id'] ?? 0), $this->acoes);
        if (!$r['ok']) {
            $_SESSION['flash_error'] = $r['error'];
        } else {
            $_SESSION['flash_success'] = $r['already_existed'] ? 'Esta ação já estava vinculada ao objetivo.' : 'Ação vinculada ao objetivo.';
        }
        $this->back((int)$o['pdi_id']);
    }

    public function acaoCriar(): void
    {
        if (!$this->guardPost()) {
            return;
        }
        $o = $this->pdis->findObjetivo((int)($_POST['objetivo_id'] ?? 0));
        if (!$o || !$this->canAccessCliente((int)$o['empresa_id'])) {
            $this->notFound('Objetivo não encontrado.');
            return;
        }
        $r = $this->pdis->criarAcaoNoObjetivo((int)$o['id'], $_POST, $this->acoes, $this->gaps, $this->avaliacoes, $this->colaboradores, $this->userId());
        if ($r['ok']) {
            AuditLogger::log('pessoas_acao_melhoria_criada', 'pessoas_acao_melhoria', $r['acao_id'], ['empresa_id' => $o['empresa_id'], 'colaborador_id' => $o['colaborador_id'], 'objetivo_id' => $o['id']]);
            $_SESSION['flash_success'] = 'Ação criada e vinculada ao objetivo.';
        } else {
            $_SESSION['flash_error'] = $r['error'];
        }
        $this->back((int)$o['pdi_id']);
    }
}

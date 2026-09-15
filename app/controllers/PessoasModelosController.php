<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\PessoaModeloAvaliacaoModel;

/**
 * Pilar de Pessoas - Avaliação de Desempenho: Modelos, Grupos e Perguntas.
 * Acesso restrito a Instituto/Cliente Admin (requireClienteAdminAccess()),
 * sempre tenant-bound via resolveScopedClienteId()/canAccessCliente().
 */
class PessoasModelosController extends BaseController
{
    private PessoaModeloAvaliacaoModel $modelos;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->modelos = new PessoaModeloAvaliacaoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $empresaId = (int)($this->resolveScopedClienteId((int)($_GET['empresa_id'] ?? 0) ?: null) ?? 0);
        $clientes = $this->clientes->all();
        $items = $empresaId > 0 ? $this->modelos->listByEmpresa($empresaId) : [];
        $this->render('pessoas/modelos/index', [
            'pageTitle' => 'Modelos de Avaliação',
            'clientes' => $clientes,
            'selectedEmpresa' => $empresaId,
            'items' => $items,
        ]);
    }

    public function create(): void
    {
        $this->requireClienteAdminAccess();
        $empresaId = (int)($this->resolveScopedClienteId((int)($_GET['empresa_id'] ?? 0) ?: null) ?? 0);
        $this->render('pessoas/modelos/create', [
            'pageTitle' => 'Novo Modelo de Avaliação',
            'clientes' => $this->clientes->all(),
            'selectedEmpresa' => $empresaId,
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
        $nome = trim((string)($_POST['nome'] ?? ''));
        if ($empresaId <= 0 || $nome === '') {
            $_SESSION['flash_error'] = 'Empresa e Nome são obrigatórios.';
            $this->redirect('index.php?route=pessoas/modeloCreate&empresa_id=' . $empresaId);
            return;
        }
        $id = $this->modelos->create([
            'empresa_id' => $empresaId,
            'nome' => $nome,
            'descricao' => $_POST['descricao'] ?? null,
            'created_by' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível criar o modelo.';
            $this->redirect('index.php?route=pessoas/modeloCreate&empresa_id=' . $empresaId);
            return;
        }
        AuditLogger::log('pessoas_modelo_criado', 'pessoas_modelo', $id, ['empresa_id' => $empresaId, 'nome' => $nome]);
        $_SESSION['flash_success'] = 'Modelo criado. Agora adicione grupos e perguntas.';
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $id);
    }

    public function edit(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $modelo = $this->modelos->find($id);
        if (!$modelo) {
            $_SESSION['flash_error'] = 'Modelo não encontrado.';
            $this->redirect('index.php?route=pessoas/modelos');
            return;
        }
        $this->render('pessoas/modelos/edit', [
            'pageTitle' => 'Editar Modelo de Avaliação',
            'modelo' => $modelo,
            'estrutura' => $this->modelos->fullStructure($id),
            'usadoPorCiclo' => $this->modelos->isUsedByCiclo($id),
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
        $modelo = $this->modelos->find($id);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $ok = $this->modelos->update($id, (int)$modelo['empresa_id'], [
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
        ]);
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível salvar o modelo.';
            $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $id);
            return;
        }
        AuditLogger::log('pessoas_modelo_alterado', 'pessoas_modelo', $id, ['empresa_id' => $modelo['empresa_id']]);
        $_SESSION['flash_success'] = 'Modelo atualizado.';
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $id);
    }

    public function toggleAtivo(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $modelo = $this->modelos->find($id);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $this->modelos->toggleAtivo($id, (int)$modelo['empresa_id']);
        AuditLogger::log('pessoas_modelo_alterado', 'pessoas_modelo', $id, ['empresa_id' => $modelo['empresa_id'], 'acao' => 'toggle_ativo']);
        $this->redirect('index.php?route=pessoas/modelos&empresa_id=' . $modelo['empresa_id']);
    }

    // ---------------------------------------------------------------
    // Grupos
    // ---------------------------------------------------------------

    public function storeGrupo(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $modeloId = (int)($_POST['modelo_id'] ?? 0);
        $modelo = $this->modelos->find($modeloId);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $nome = trim((string)($_POST['nome'] ?? ''));
        if ($nome === '') {
            $_SESSION['flash_error'] = 'Nome do grupo é obrigatório.';
            $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modeloId);
            return;
        }
        $this->modelos->createGroup($modeloId, ['nome' => $nome, 'descricao' => $_POST['descricao'] ?? null]);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modeloId);
    }

    public function updateGrupo(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $grupo = $this->modelos->findGroup($id);
        if (!$grupo) {
            http_response_code(404);
            echo 'Grupo não encontrado.';
            return;
        }
        $modelo = $this->modelos->find((int)$grupo['modelo_id']);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $this->modelos->updateGroup($id, [
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'ordem' => $_POST['ordem'] ?? $grupo['ordem'],
        ]);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
    }

    public function deleteGrupo(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $grupo = $this->modelos->findGroup($id);
        if (!$grupo) {
            http_response_code(404);
            echo 'Grupo não encontrado.';
            return;
        }
        $modelo = $this->modelos->find((int)$grupo['modelo_id']);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        if ($this->modelos->isUsedByCiclo((int)$modelo['id'])) {
            $_SESSION['flash_error'] = 'Este modelo já está em uso por um ciclo e não pode ter grupos removidos.';
            $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
            return;
        }
        $this->modelos->deleteGroup($id);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
    }

    // ---------------------------------------------------------------
    // Perguntas
    // ---------------------------------------------------------------

    public function storePergunta(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $grupoId = (int)($_POST['grupo_id'] ?? 0);
        $grupo = $this->modelos->findGroup($grupoId);
        if (!$grupo) {
            http_response_code(404);
            echo 'Grupo não encontrado.';
            return;
        }
        $modelo = $this->modelos->find((int)$grupo['modelo_id']);
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $pergunta = trim((string)($_POST['pergunta'] ?? ''));
        if ($pergunta === '') {
            $_SESSION['flash_error'] = 'Texto da pergunta é obrigatório.';
            $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
            return;
        }
        $this->modelos->createQuestion($grupoId, [
            'pergunta' => $pergunta,
            'orientacao' => $_POST['orientacao'] ?? null,
            'peso' => $_POST['peso'] ?? 1,
            'obrigatoria' => isset($_POST['obrigatoria']),
        ]);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
    }

    public function updatePergunta(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $pergunta = $this->modelos->findQuestion($id);
        if (!$pergunta) {
            http_response_code(404);
            echo 'Pergunta não encontrada.';
            return;
        }
        $grupo = $this->modelos->findGroup((int)$pergunta['grupo_id']);
        $modelo = $grupo ? $this->modelos->find((int)$grupo['modelo_id']) : null;
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        $this->modelos->updateQuestion($id, [
            'pergunta' => $_POST['pergunta'] ?? '',
            'orientacao' => $_POST['orientacao'] ?? null,
            'peso' => $_POST['peso'] ?? 1,
            'ordem' => $_POST['ordem'] ?? $pergunta['ordem'],
            'obrigatoria' => isset($_POST['obrigatoria']),
        ]);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
    }

    public function deletePergunta(): void
    {
        $this->requireClienteAdminAccess();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $pergunta = $this->modelos->findQuestion($id);
        if (!$pergunta) {
            http_response_code(404);
            echo 'Pergunta não encontrada.';
            return;
        }
        $grupo = $this->modelos->findGroup((int)$pergunta['grupo_id']);
        $modelo = $grupo ? $this->modelos->find((int)$grupo['modelo_id']) : null;
        if (!$modelo) {
            http_response_code(404);
            echo 'Modelo não encontrado.';
            return;
        }
        if ($this->modelos->isUsedByCiclo((int)$modelo['id'])) {
            $_SESSION['flash_error'] = 'Este modelo já está em uso por um ciclo e não pode ter perguntas removidas.';
            $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
            return;
        }
        $this->modelos->deleteQuestion($id);
        $this->redirect('index.php?route=pessoas/modeloEdit&id=' . $modelo['id']);
    }
}

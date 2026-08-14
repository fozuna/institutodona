<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\AtaModel;

/**
 * Item 05B: acervo interno de Atas da Biblioteca. Instituto-only em
 * profundidade: a rota atas/* fica sob AccessControl::ADMIN_MODULE (só
 * Instituto tem esse módulo liberado - já bloqueia Cliente Admin/Cliente/
 * Reader/Consultor com 404 oculto antes de chegar aqui), e cada ação
 * ainda confirma Auth::isInstituto() explicitamente (requireInstitutoOnly),
 * sem depender só do menu estar escondido. O próprio AtaModel também
 * recusa qualquer leitura/escrita se o usuário não for Instituto -
 * terceira camada, independente do Controller.
 *
 * Sem relação com o Portal público da Biblioteca (ManualPortalTokenModel/
 * ManuaisController::portal()/download()): nenhum desses componentes é
 * tocado ou importado aqui - Atas não têm link permanente nem endpoint
 * público.
 */
class AtasController extends BaseController
{
    private AtaModel $atas;

    public function __construct()
    {
        $this->atas = new AtaModel();
    }

    private function requireInstitutoOnly(): void
    {
        $this->requireLogin();
        if (!Auth::isInstituto()) {
            $this->denyAccess('Acesso restrito ao Instituto.', (string)($_GET['route'] ?? ''), null, $this->isAjaxRequest());
        }
    }

    public function index(): void
    {
        $this->requireInstitutoOnly();
        $nome = trim((string)($_GET['nome'] ?? ''));
        $items = $this->atas->list(['nome' => $nome]);
        $this->render('atas/index', [
            'items' => $items,
            'searchNome' => $nome,
        ]);
    }

    public function create(): void
    {
        $this->requireInstitutoOnly();
        $this->render('atas/create', []);
    }

    public function store(): void
    {
        $this->requireInstitutoOnly();
        $isAjax = $this->isAjaxRequest();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'CSRF inválido';
            return;
        }

        $nome = trim((string)($_POST['nome'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        if ($nome === '') {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Informe o nome/título da ata.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Informe o nome/título da ata.';
            return;
        }
        if (mb_strlen($descricao) > 500) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Descrição deve ter no máximo 500 caracteres.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Descrição deve ter no máximo 500 caracteres.';
            return;
        }

        $file = $_FILES['arquivo'] ?? null;
        if (!is_array($file) || empty($file['name'])) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo obrigatório.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo obrigatório.';
            return;
        }
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $msg = ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
                ? 'Arquivo excede o limite permitido pelo servidor.'
                : 'Falha no upload do arquivo.';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $msg;
            return;
        }
        if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo inválido.';
            return;
        }

        $sizeBytes = (int)($file['size'] ?? 0);
        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)@finfo_file($finfo, (string)$file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            @finfo_close($finfo);
        }
        $validated = AtaModel::validateUpload((string)$file['name'], $sizeBytes, $mime);
        if (empty($validated['ok'])) {
            http_response_code(400);
            $message = (string)($validated['message'] ?? 'Arquivo inválido.');
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $message;
            return;
        }
        $ext = (string)$validated['ext'];

        $dir = AtaModel::storageDirFor();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível criar diretório de armazenamento.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Não foi possível criar diretório de armazenamento.';
            return;
        }
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;
        if (!@move_uploaded_file((string)$file['tmp_name'], $dest)) {
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Falha ao salvar arquivo.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Falha ao salvar arquivo.';
            return;
        }

        $relativePath = 'storage/atas/' . $storedName;
        $ataId = $this->atas->create([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'arquivo' => $relativePath,
            'tipo_arquivo' => $ext,
            'tamanho' => $sizeBytes,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        if ($ataId <= 0) {
            @unlink($dest);
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Falha ao registrar a ata no banco.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Falha ao registrar a ata no banco.';
            return;
        }

        AuditLogger::log('ata_upload', 'ata', $ataId, [
            'nome' => $nome,
            'tipo_arquivo' => $ext,
            'tamanho' => $sizeBytes,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);

        $redirect = 'index.php?route=atas/index';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'redirect' => $redirect], JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION['flash_success'] = 'Ata enviada com sucesso.';
        header('Location: ' . $redirect);
    }

    public function download(): void
    {
        $this->requireInstitutoOnly();
        $id = (int)($_GET['id'] ?? 0);
        $ata = $this->atas->find($id);
        if (!$ata) {
            http_response_code(404);
            echo 'Ata não encontrada.';
            return;
        }
        $path = dirname(__DIR__, 2) . '/' . ltrim((string)$ata['arquivo'], '/');
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }
        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$ata['nome']) ?: ('ata_' . $id);
        $downloadName .= '.' . ($ata['tipo_arquivo'] ?? pathinfo($path, PATHINFO_EXTENSION));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . (string)filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function delete(): void
    {
        $this->requireInstitutoOnly();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $ata = $this->atas->find($id);
        if (!$ata) {
            http_response_code(404);
            echo 'Ata não encontrada.';
            return;
        }
        $absPath = dirname(__DIR__, 2) . '/' . ltrim((string)($ata['arquivo'] ?? ''), '/');
        $ok = $this->atas->delete($id);
        if (!$ok) {
            http_response_code(500);
            echo 'Falha ao excluir ata.';
            return;
        }
        if (is_file($absPath)) {
            @unlink($absPath);
        }
        AuditLogger::log('ata_delete', 'ata', $id, [
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Ata excluída com sucesso.';
        header('Location: index.php?route=atas/index');
    }
}

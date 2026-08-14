<?php
namespace App\Models;

use App\Core\Auth;

/**
 * Item 05B: acervo interno de Atas da Biblioteca. Documento interno do
 * Instituto - sem empresa_id/departamento_id (não é tenant-scoped, ao
 * contrário de ManualModel). O isolamento "Instituto only" é reforçado
 * aqui, no próprio Model (defesa em profundidade), e não apenas pela rota
 * (ADMIN_MODULE) ou pelo Controller: todo método de leitura/escrita
 * verifica Auth::isInstituto() e recusa silenciosamente se não for.
 */
class AtaModel extends BaseModel
{
    private const ALLOWED_TYPES = ['pdf', 'doc', 'docx'];

    private const MIME_BY_EXT = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS atas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                descricao VARCHAR(500) NULL,
                arquivo VARCHAR(255) NOT NULL,
                tipo_arquivo VARCHAR(10) NOT NULL,
                tamanho INT UNSIGNED NOT NULL DEFAULT 0,
                usuario_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_atas_nome (nome),
                KEY idx_atas_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    public function list(array $filters = []): array
    {
        $this->ensureTable();
        if (!Auth::isInstituto()) {
            return [];
        }
        $params = [];
        $sql = "SELECT a.id, a.nome, a.descricao, a.arquivo, a.tipo_arquivo, a.tamanho, a.usuario_id, a.created_at, u.nome AS usuario_nome
                FROM atas a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE 1=1";
        if (!empty($filters['nome'])) {
            $sql .= ' AND a.nome LIKE :nome';
            $params['nome'] = '%' . trim((string)$filters['nome']) . '%';
        }
        $sql .= ' ORDER BY a.created_at DESC, a.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        if (!Auth::isInstituto()) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT a.id, a.nome, a.descricao, a.arquivo, a.tipo_arquivo, a.tamanho, a.usuario_id, a.created_at, u.nome AS usuario_nome
                                     FROM atas a
                                     LEFT JOIN usuarios u ON u.id = a.usuario_id
                                     WHERE a.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        if (!Auth::isInstituto()) {
            return 0;
        }
        $nome = trim((string)($data['nome'] ?? ''));
        $arquivo = (string)($data['arquivo'] ?? '');
        $tipoArquivo = (string)($data['tipo_arquivo'] ?? '');
        if ($nome === '' || $arquivo === '' || $tipoArquivo === '') {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO atas (nome, descricao, arquivo, tipo_arquivo, tamanho, usuario_id) VALUES (:nome, :descricao, :arquivo, :tipo_arquivo, :tamanho, :usuario_id)');
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $data['descricao'] !== null && $data['descricao'] !== '' ? trim((string)$data['descricao']) : null,
            'arquivo' => $arquivo,
            'tipo_arquivo' => $tipoArquivo,
            'tamanho' => (int)($data['tamanho'] ?? 0),
            'usuario_id' => isset($data['usuario_id']) ? (int)$data['usuario_id'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        if (!Auth::isInstituto()) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM atas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function allowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    public static function extensionFromUpload(string $clientFilename): ?string
    {
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_TYPES, true) ? $ext : null;
    }

    /**
     * Mesmo mecanismo de validação usado em ManualModel::validateUpload()
     * (extensão + MIME real detectado por finfo + tamanho), mas com a
     * allowlist restrita a PDF/DOC/DOCX pedida para Atas - não confia
     * apenas na extensão informada pelo navegador.
     */
    public static function validateUpload(string $clientFilename, int $sizeBytes, string $detectedMime, int $maxBytes = 20971520): array
    {
        $ext = self::extensionFromUpload($clientFilename);
        if ($ext === null) {
            return ['ok' => false, 'error' => 'ext_invalid', 'message' => 'Tipo de arquivo inválido. Permitidos: PDF, DOC, DOCX.'];
        }
        if ($sizeBytes <= 0) {
            return ['ok' => false, 'error' => 'size_invalid', 'message' => 'Arquivo inválido.'];
        }
        if ($sizeBytes > $maxBytes) {
            return ['ok' => false, 'error' => 'size_exceeded', 'message' => 'Arquivo excede o limite de 20MB.'];
        }
        $mime = strtolower(trim($detectedMime));
        $allowed = self::MIME_BY_EXT[$ext] ?? [];
        if (!in_array($mime, $allowed, true)) {
            return ['ok' => false, 'error' => 'mime_invalid', 'message' => 'Tipo MIME inválido para o arquivo selecionado.'];
        }
        return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
    }

    public static function storageDirFor(): string
    {
        return dirname(__DIR__, 2) . '/storage/atas';
    }
}

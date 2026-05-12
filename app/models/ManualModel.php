<?php
namespace App\Models;

use App\Core\Auth;

class ManualModel extends BaseModel
{
    private const ALLOWED_TYPES = [
        'pdf',
        'doc', 'docx',
        'txt', 'rtf',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
    ];

    private const MIME_BY_EXT = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain'],
        'rtf' => ['application/rtf', 'text/rtf', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'svg' => ['image/svg+xml', 'text/xml', 'application/xml', 'application/octet-stream'],
    ];

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS manuais (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                departamento_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                descricao VARCHAR(500) NULL,
                arquivo VARCHAR(255) NOT NULL,
                tipo_arquivo VARCHAR(10) NOT NULL,
                tamanho INT UNSIGNED NOT NULL DEFAULT 0,
                usuario_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_manuais_empresa (empresa_id),
                INDEX idx_manuais_departamento (departamento_id),
                INDEX idx_manuais_nome (nome),
                CONSTRAINT fk_manuais_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
                CONSTRAINT fk_manuais_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    public function list(array $filters = []): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('m.empresa_id', $params, 'ml');
        $sql = "SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                       c.nome_empresa AS empresa_nome, d.nome AS departamento_nome, u.nome AS usuario_nome
                FROM manuais m
                JOIN clientes c ON c.id = m.empresa_id
                JOIN departamentos d ON d.id = m.departamento_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE $scope";

        if (!empty($filters['empresa_id'])) {
            $sql .= ' AND m.empresa_id = :empresa_id';
            $params['empresa_id'] = (int)$filters['empresa_id'];
        }
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND m.departamento_id = :departamento_id';
            $params['departamento_id'] = (int)$filters['departamento_id'];
        }
        if (!empty($filters['nome'])) {
            $sql .= ' AND m.nome LIKE :nome';
            $params['nome'] = '%' . trim((string)$filters['nome']) . '%';
        }

        $sql .= ' ORDER BY m.created_at DESC, m.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('m.empresa_id', $params, 'mf');
        $stmt = $this->db->prepare("SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                                           c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                                    FROM manuais m
                                    JOIN clientes c ON c.id = m.empresa_id
                                    JOIN departamentos d ON d.id = m.departamento_id
                                    WHERE m.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAny(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                                           c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                                    FROM manuais m
                                    JOIN clientes c ON c.id = m.empresa_id
                                    JOIN departamentos d ON d.id = m.departamento_id
                                    WHERE m.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $empresaId = (int)$this->normalizeScopedClienteId(isset($data['empresa_id']) ? (int)$data['empresa_id'] : null);
        if ($empresaId <= 0 || !$this->canAccessClienteId($empresaId)) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO manuais (empresa_id, departamento_id, nome, descricao, arquivo, tipo_arquivo, tamanho, usuario_id) VALUES (:empresa_id, :departamento_id, :nome, :descricao, :arquivo, :tipo_arquivo, :tamanho, :usuario_id)');
        $stmt->execute([
            'empresa_id' => $empresaId,
            'departamento_id' => (int)$data['departamento_id'],
            'nome' => trim((string)$data['nome']),
            'descricao' => $data['descricao'] !== null ? trim((string)$data['descricao']) : null,
            'arquivo' => (string)$data['arquivo'],
            'tipo_arquivo' => (string)$data['tipo_arquivo'],
            'tamanho' => (int)$data['tamanho'],
            'usuario_id' => isset($data['usuario_id']) ? (int)$data['usuario_id'] : null,
        ]);
        return (int)$this->db->lastInsertId();
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

    public static function categoryFromExtension(string $ext): string
    {
        $ext = strtolower($ext);
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'], true)) {
            return 'imagens';
        }
        if (in_array($ext, ['xls', 'xlsx'], true)) {
            return 'planilhas';
        }
        if (in_array($ext, ['ppt', 'pptx'], true)) {
            return 'apresentacoes';
        }
        if (in_array($ext, ['txt', 'rtf'], true)) {
            return 'texto';
        }
        return 'documentos';
    }

    public static function validateUpload(string $clientFilename, int $sizeBytes, string $detectedMime, int $maxBytes = 52428800): array
    {
        $ext = self::extensionFromUpload($clientFilename);
        if ($ext === null) {
            return ['ok' => false, 'error' => 'ext_invalid', 'message' => 'Tipo de arquivo inválido.'];
        }
        if ($sizeBytes <= 0) {
            return ['ok' => false, 'error' => 'size_invalid', 'message' => 'Arquivo inválido.'];
        }
        if ($sizeBytes > $maxBytes) {
            return ['ok' => false, 'error' => 'size_exceeded', 'message' => 'Arquivo excede o limite de 50MB.'];
        }
        $mime = strtolower(trim($detectedMime));
        $allowed = self::MIME_BY_EXT[$ext] ?? [];
        if (!in_array($mime, $allowed, true)) {
            return ['ok' => false, 'error' => 'mime_invalid', 'message' => 'Tipo MIME inválido para o arquivo selecionado.'];
        }
        return [
            'ok' => true,
            'ext' => $ext,
            'mime' => $mime,
            'category' => self::categoryFromExtension($ext),
        ];
    }

    public static function storageDirFor(int $empresaId, int $departamentoId, ?string $category = null): string
    {
        $base = dirname(__DIR__, 2) . '/storage/manuais/' . $empresaId . '/' . $departamentoId;
        if ($category !== null && $category !== '') {
            $safe = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower($category)) ?: 'documentos';
            return $base . '/' . $safe;
        }
        return $base;
    }

    public function portalCount(array $empresaIds, array $filters = []): int
    {
        $this->ensureTable();
        if (empty($empresaIds)) {
            return 0;
        }
        $params = [];
        $sql = 'SELECT COUNT(*) FROM manuais m WHERE ' . $this->companyScopeClause($empresaIds, $params, 'mpc');
        $sql .= $this->portalFilterClause($filters, $params, 'mpcf');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function portalList(array $empresaIds, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $this->ensureTable();
        if (empty($empresaIds)) {
            return [];
        }
        $params = [];
        $sql = "SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.tipo_arquivo, m.tamanho, m.created_at,
                       c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                FROM manuais m
                JOIN clientes c ON c.id = m.empresa_id
                JOIN departamentos d ON d.id = m.departamento_id
                WHERE " . $this->companyScopeClause($empresaIds, $params, 'mpl');
        $sql .= $this->portalFilterClause($filters, $params, 'mplf');
        $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $perPage), \PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, ($page - 1) * $perPage), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function companyScopeClause(array $empresaIds, array &$params, string $prefix): string
    {
        $placeholders = [];
        foreach (array_values($empresaIds) as $i => $empresaId) {
            $key = $prefix . '_e' . $i;
            $params[$key] = (int)$empresaId;
            $placeholders[] = ':' . $key;
        }
        return 'm.empresa_id IN (' . implode(',', $placeholders) . ')';
    }

    private function portalFilterClause(array $filters, array &$params, string $prefix): string
    {
        $sql = '';
        if (!empty($filters['departamento_id'])) {
            $params[$prefix . '_dep'] = (int)$filters['departamento_id'];
            $sql .= ' AND m.departamento_id = :' . $prefix . '_dep';
        }
        if (!empty($filters['q'])) {
            $params[$prefix . '_q'] = '%' . trim((string)$filters['q']) . '%';
            $sql .= ' AND (m.nome LIKE :' . $prefix . '_q OR m.descricao LIKE :' . $prefix . '_q)';
        }
        if (!empty($filters['data_de'])) {
            $params[$prefix . '_de'] = (string)$filters['data_de'] . ' 00:00:00';
            $sql .= ' AND m.created_at >= :' . $prefix . '_de';
        }
        if (!empty($filters['data_ate'])) {
            $params[$prefix . '_ate'] = (string)$filters['data_ate'] . ' 23:59:59';
            $sql .= ' AND m.created_at <= :' . $prefix . '_ate';
        }
        return $sql;
    }

    public static function canManage(): bool
    {
        $role = (string)(Auth::user()['tipo_acesso'] ?? '');
        return $role === 'instituto' || $role === 'cliente_admin';
    }
}

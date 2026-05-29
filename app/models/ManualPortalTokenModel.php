<?php
namespace App\Models;

class ManualPortalTokenModel extends BaseModel
{
    private function ensureColumns(): void
    {
        $this->ensureTable();
        try {
            if (!\App\Database\Database::columnExists('manual_portal_tokens', 'scope_ids_json')) {
                $this->db->exec('ALTER TABLE manual_portal_tokens ADD COLUMN scope_ids_json TEXT NULL');
            }
            if (!\App\Database\Database::columnExists('manual_portal_tokens', 'filters_json')) {
                $this->db->exec('ALTER TABLE manual_portal_tokens ADD COLUMN filters_json TEXT NULL');
            }
        } catch (\PDOException $e) {
        }
    }

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS manual_portal_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                expira_em DATETIME NULL,
                scope_ids_json TEXT NULL,
                filters_json TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_manual_portal_empresa (empresa_id),
                CONSTRAINT fk_manual_portal_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    public function issue(int $empresaId, ?string $expiraEm = null, ?array $scopeIds = null, ?array $filters = null): string
    {
        $this->ensureColumns();
        $this->db->prepare('UPDATE manual_portal_tokens SET ativo = 0 WHERE empresa_id = :empresa_id')->execute([
            'empresa_id' => $empresaId,
        ]);
        $token = bin2hex(random_bytes(24));
        $hasScopeCol = \App\Database\Database::columnExists('manual_portal_tokens', 'scope_ids_json');
        $hasFiltersCol = \App\Database\Database::columnExists('manual_portal_tokens', 'filters_json');
        $sql = 'INSERT INTO manual_portal_tokens (empresa_id, token, ativo, expira_em';
        $sql .= $hasScopeCol ? ', scope_ids_json' : '';
        $sql .= $hasFiltersCol ? ', filters_json' : '';
        $sql .= ') VALUES (:empresa_id, :token, 1, :expira_em';
        $sql .= $hasScopeCol ? ', :scope_ids_json' : '';
        $sql .= $hasFiltersCol ? ', :filters_json' : '';
        $sql .= ')';
        $stmt = $this->db->prepare($sql);
        $payload = [
            'empresa_id' => $empresaId,
            'token' => $token,
            'expira_em' => $expiraEm,
        ];
        if ($hasScopeCol) {
            $payload['scope_ids_json'] = $scopeIds !== null ? json_encode(array_values(array_unique(array_map('intval', $scopeIds))), JSON_UNESCAPED_UNICODE) : null;
        }
        if ($hasFiltersCol) {
            $payload['filters_json'] = $filters !== null ? json_encode($filters, JSON_UNESCAPED_UNICODE) : null;
        }
        $stmt->execute([
            ...$payload,
        ]);
        return $token;
    }

    public function findValid(string $token): ?array
    {
        $this->ensureColumns();
        $columns = ['id', 'empresa_id', 'token', 'ativo', 'expira_em', 'created_at'];
        if (\App\Database\Database::columnExists('manual_portal_tokens', 'scope_ids_json')) {
            $columns[] = 'scope_ids_json';
        }
        if (\App\Database\Database::columnExists('manual_portal_tokens', 'filters_json')) {
            $columns[] = 'filters_json';
        }
        $stmt = $this->db->prepare('SELECT ' . implode(',', $columns) . ' FROM manual_portal_tokens WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ((int)($row['ativo'] ?? 0) !== 1) {
            return null;
        }
        if (!empty($row['expira_em']) && strtotime((string)$row['expira_em']) < time()) {
            return null;
        }
        return $row;
    }
}

<?php
namespace App\Models;

class ManualPortalTokenModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS manual_portal_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                expira_em DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_manual_portal_empresa (empresa_id),
                CONSTRAINT fk_manual_portal_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    public function issue(int $empresaId, ?string $expiraEm = null): string
    {
        $this->ensureTable();
        $this->db->prepare('UPDATE manual_portal_tokens SET ativo = 0 WHERE empresa_id = :empresa_id')->execute([
            'empresa_id' => $empresaId,
        ]);
        $token = bin2hex(random_bytes(24));
        $stmt = $this->db->prepare('INSERT INTO manual_portal_tokens (empresa_id, token, ativo, expira_em) VALUES (:empresa_id, :token, 1, :expira_em)');
        $stmt->execute([
            'empresa_id' => $empresaId,
            'token' => $token,
            'expira_em' => $expiraEm,
        ]);
        return $token;
    }

    public function findValid(string $token): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, empresa_id, token, ativo, expira_em, created_at FROM manual_portal_tokens WHERE token = :token LIMIT 1');
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

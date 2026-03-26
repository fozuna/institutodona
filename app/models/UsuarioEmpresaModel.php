<?php
namespace App\Models;

class UsuarioEmpresaModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS usuario_empresas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                cliente_id INT NOT NULL,
                origem ENUM('direto','herdado') NOT NULL DEFAULT 'direto',
                permitido TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_usuario_cliente (usuario_id, cliente_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    private function ensureClienteColumns(): void
    {
        try {
            if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('clientes', 'acesso_restrito')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN acesso_restrito TINYINT(1) NOT NULL DEFAULT 0');
            }
        } catch (\PDOException $e) {
        }
    }

    public function syncForUser(int $usuarioId, array $selectedClientes): array
    {
        $this->ensureTable();
        $this->ensureClienteColumns();
        $usuarioId = (int)$usuarioId;
        if ($usuarioId <= 0) {
            return [];
        }
        $selectedClientes = array_values(array_unique(array_filter(array_map('intval', $selectedClientes), fn(int $id): bool => $id > 0)));
        $final = [];
        foreach ($selectedClientes as $rootId) {
            $desc = \App\Core\TenantScopeResolver::descendantsInclusive($rootId);
            if (empty($desc)) {
                continue;
            }
            foreach ($desc as $cid) {
                $cid = (int)$cid;
                if ($cid <= 0) {
                    continue;
                }
                if (!isset($final[$cid])) {
                    $final[$cid] = in_array($cid, $selectedClientes, true) ? 'direto' : 'herdado';
                } elseif ($final[$cid] !== 'direto' && in_array($cid, $selectedClientes, true)) {
                    $final[$cid] = 'direto';
                }
            }
        }
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM usuario_empresas WHERE usuario_id = :uid');
            $del->execute(['uid' => $usuarioId]);
            if (!empty($final)) {
                $ins = $this->db->prepare('INSERT INTO usuario_empresas (usuario_id, cliente_id, origem, permitido) VALUES (:uid, :cid, :origem, 1)');
                foreach ($final as $cid => $origem) {
                    $ins->execute(['uid' => $usuarioId, 'cid' => (int)$cid, 'origem' => $origem]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        $ids = array_map('intval', array_keys($final));
        sort($ids);
        return $ids;
    }

    public function selectedForUser(int $usuarioId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT cliente_id FROM usuario_empresas WHERE usuario_id = :uid AND origem = 'direto' AND permitido = 1 ORDER BY cliente_id");
        $stmt->execute(['uid' => $usuarioId]);
        return array_values(array_map('intval', array_column($stmt->fetchAll(), 'cliente_id')));
    }

    public function allForUser(int $usuarioId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT ue.cliente_id, ue.origem, c.nome_empresa
                                    FROM usuario_empresas ue
                                    JOIN clientes c ON c.id = ue.cliente_id
                                    WHERE ue.usuario_id = :uid AND ue.permitido = 1
                                    ORDER BY c.nome_empresa");
        $stmt->execute(['uid' => $usuarioId]);
        return $stmt->fetchAll();
    }
}

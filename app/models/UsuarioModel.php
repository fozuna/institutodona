<?php
namespace App\Models;

class UsuarioModel extends BaseModel
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome, email, senha_hash, tipo_acesso, id_cliente FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('COALESCE(id_cliente, -1)', $params, 'uif');
        $stmt = $this->db->prepare("SELECT id, nome, email, senha_hash, tipo_acesso, id_cliente FROM usuarios WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $params = [];
        $scope = $this->tenantInCondition('COALESCE(id_cliente, -1)', $params, 'uia');
        $stmt = $this->db->prepare("SELECT id, nome, email, tipo_acesso, id_cliente FROM usuarios WHERE $scope ORDER BY nome");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $data['id_cliente'] = $this->normalizeScopedClienteId(isset($data['id_cliente']) ? (int)$data['id_cliente'] : null);
        if (($data['tipo_acesso'] ?? '') !== 'instituto' && ($data['id_cliente'] ?? 0) > 0 && !$this->canAccessClienteId((int)$data['id_cliente'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:nome, :email, :senha_hash, :tipo_acesso, :id_cliente)');
        $stmt->execute([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha_hash' => $data['senha_hash'],
            'tipo_acesso' => $data['tipo_acesso'],
            'id_cliente' => $data['id_cliente'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['id_cliente'] = $this->normalizeScopedClienteId(isset($data['id_cliente']) ? (int)$data['id_cliente'] : null);
        $params = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'tipo_acesso' => $data['tipo_acesso'],
            'id_cliente' => $data['id_cliente'] ?? null,
            'id' => $id,
        ];
        $scope = $this->tenantInCondition('COALESCE(id_cliente, -1)', $params, 'uiu');
        $stmt = $this->db->prepare("UPDATE usuarios SET nome = :nome, email = :email, tipo_acesso = :tipo_acesso, id_cliente = :id_cliente WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $senha_hash): bool
    {
        $params = ['senha_hash' => $senha_hash, 'id' => $id];
        $scope = $this->tenantInCondition('COALESCE(id_cliente, -1)', $params, 'uip');
        $stmt = $this->db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('COALESCE(id_cliente, -1)', $params, 'uid');
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }
}

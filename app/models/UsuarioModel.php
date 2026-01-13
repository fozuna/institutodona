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
        $stmt = $this->db->prepare('SELECT id, nome, email, senha_hash, tipo_acesso, id_cliente FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, nome, email, tipo_acesso, id_cliente FROM usuarios ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
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
        $stmt = $this->db->prepare('UPDATE usuarios SET nome = :nome, email = :email, tipo_acesso = :tipo_acesso, id_cliente = :id_cliente WHERE id = :id');
        return $stmt->execute([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'tipo_acesso' => $data['tipo_acesso'],
            'id_cliente' => $data['id_cliente'] ?? null,
            'id' => $id,
        ]);
    }

    public function updatePassword(int $id, string $senha_hash): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        return $stmt->execute(['senha_hash' => $senha_hash, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

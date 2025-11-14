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
}
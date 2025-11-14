<?php
namespace App\Models;

class ClienteModel extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato FROM clientes ORDER BY nome_empresa');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome_empresa, :cnpj, :contato)');
        $stmt->execute([
            'nome_empresa' => $data['nome_empresa'],
            'cnpj' => $data['CNPJ'],
            'contato' => $data['contato'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE clientes SET nome_empresa = :nome_empresa, CNPJ = :cnpj, contato = :contato WHERE id = :id');
        return $stmt->execute([
            'nome_empresa' => $data['nome_empresa'],
            'cnpj' => $data['CNPJ'],
            'contato' => $data['contato'] ?? null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
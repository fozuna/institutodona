<?php
namespace App\Models;

class UnidadeMedidaModel extends BaseModel
{
    public function activeAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nome, simbolo, tipo, fator_conversao_base, ativo
             FROM unidades_medida
             WHERE ativo = 1
             ORDER BY tipo, nome'
        );
        return $stmt->fetchAll();
    }

    public function findActive(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT id, nome, simbolo, tipo, fator_conversao_base, ativo
             FROM unidades_medida
             WHERE id = :id AND ativo = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

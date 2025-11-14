<?php
namespace App\Models;

class AplicacaoModel extends BaseModel
{
    public function byCliente(int $idCliente): array
    {
        $sql = 'SELECT a.id, a.status, a.id_metodologia, m.item_pilar, p.nome AS pilar_nome
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                WHERE a.id_cliente = :id_cliente
                ORDER BY a.status, p.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }

    public function statsByPilar(?int $idCliente = null): array
    {
        $where = '';
        $params = [];
        if ($idCliente !== null) {
            $where = 'WHERE a.id_cliente = :id_cliente';
            $params['id_cliente'] = $idCliente;
        }
        $sql = "SELECT p.nome AS pilar, a.status, COUNT(*) AS total
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                $where
                GROUP BY p.nome, a.status
                ORDER BY p.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(int $idCliente, int $idMetodologia, string $status): int
    {
        $stmt = $this->db->prepare('INSERT INTO aplicacoes (id_cliente, id_metodologia, status) VALUES (:id_cliente, :id_metodologia, :status)');
        $stmt->execute([
            'id_cliente' => $idCliente,
            'id_metodologia' => $idMetodologia,
            'status' => $status,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
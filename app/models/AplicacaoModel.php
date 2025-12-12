<?php
namespace App\Models;

class AplicacaoModel extends BaseModel
{
    public function byCliente(int $idCliente): array
    {
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, p.nome' : 'ORDER BY p.nome';

        $sql = "SELECT a.id, a.status, a.id_metodologia, a.id_cliente, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                WHERE a.id_cliente = :id_cliente
                $order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, cli.nome_empresa, p.nome' : 'ORDER BY cli.nome_empresa, p.nome';

        $sql = "SELECT a.id, a.status, a.id_metodologia, a.id_cliente, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                $order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
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

    public function create(int $idCliente, int $idMetodologia, string $status, ?int $consultorId = null, ?string $dataPrevista = null): int
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO aplicacoes (id_cliente, id_metodologia, status, consultor_id, data_prevista) VALUES (:id_cliente, :id_metodologia, :status, :consultor_id, :data_prevista)');
            $stmt->execute([
                'id_cliente' => $idCliente,
                'id_metodologia' => $idMetodologia,
                'status' => $status,
                'consultor_id' => $consultorId,
                'data_prevista' => $dataPrevista,
            ]);
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare('INSERT INTO aplicacoes (id_cliente, id_metodologia, status) VALUES (:id_cliente, :id_metodologia, :status)');
            $stmt->execute([
                'id_cliente' => $idCliente,
                'id_metodologia' => $idMetodologia,
                'status' => $status,
            ]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $idAplicacao, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE aplicacoes SET status = :status WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'id' => $idAplicacao,
        ]);
    }

    public function updateSchedule(int $idAplicacao, ?string $dataPrevista, ?int $consultorId): bool
    {
        try {
            $stmt = $this->db->prepare('UPDATE aplicacoes SET data_prevista = :data_prevista, consultor_id = :consultor_id WHERE id = :id');
            return $stmt->execute([
                'data_prevista' => $dataPrevista,
                'consultor_id' => $consultorId,
                'id' => $idAplicacao,
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function find(int $idAplicacao): ?array
    {
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';

        $sql = "SELECT a.id, a.id_cliente, a.id_metodologia, a.status, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                WHERE a.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idAplicacao]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function delete(int $idAplicacao): bool
    {
        $stmt = $this->db->prepare('DELETE FROM aplicacoes WHERE id = :id');
        return $stmt->execute(['id' => $idAplicacao]);
    }
}

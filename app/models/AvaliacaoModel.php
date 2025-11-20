<?php
namespace App\Models;

class AvaliacaoModel extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO avaliacoes (id_cliente, financeiro_nota, mercado_nota, pessoas_nota, processo_nota, financeiro_realidade, mercado_realidade, pessoas_realidade, processo_realidade, total, realidade_media, respostas_json, created_at) VALUES (:id_cliente, :financeiro_nota, :mercado_nota, :pessoas_nota, :processo_nota, :financeiro_realidade, :mercado_realidade, :pessoas_realidade, :processo_realidade, :total, :realidade_media, :respostas_json, NOW())');
        $stmt->execute([
            'id_cliente' => $data['id_cliente'],
            'financeiro_nota' => $data['financeiro_nota'],
            'mercado_nota' => $data['mercado_nota'],
            'pessoas_nota' => $data['pessoas_nota'],
            'processo_nota' => $data['processo_nota'],
            'financeiro_realidade' => $data['financeiro_realidade'],
            'mercado_realidade' => $data['mercado_realidade'],
            'pessoas_realidade' => $data['pessoas_realidade'],
            'processo_realidade' => $data['processo_realidade'],
            'total' => $data['total'],
            'realidade_media' => $data['realidade_media'],
            'respostas_json' => $data['respostas_json'],
        ]);
        return (int)$this->db->lastInsertId();
    }
}
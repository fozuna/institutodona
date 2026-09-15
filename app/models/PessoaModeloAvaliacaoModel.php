<?php
namespace App\Models;

/**
 * Pilar de Pessoas - Avaliação de Desempenho: Modelo de Avaliação, seus
 * Grupos e Perguntas (pessoas_modelos_avaliacao / pessoas_modelos_grupos /
 * pessoas_modelos_perguntas). Mesma consolidação de responsabilidade já
 * usada em ManualModel (manuais + manual_filial_links) - um Model por
 * "agregado", não necessariamente um Model por tabela.
 */
class PessoaModeloAvaliacaoModel extends BaseModel
{
    public function listByEmpresa(int $empresaId): array
    {
        $params = ['eid' => $empresaId];
        $scope = $this->tenantInCondition('m.empresa_id', $params, 'pmle');
        $stmt = $this->db->prepare(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM pessoas_modelos_grupos g WHERE g.modelo_id = m.id) AS total_grupos,
                    (SELECT COUNT(*) FROM pessoas_modelos_perguntas p
                        JOIN pessoas_modelos_grupos g2 ON g2.id = p.grupo_id
                        WHERE g2.modelo_id = m.id) AS total_perguntas,
                    (SELECT COUNT(*) FROM pessoas_ciclos_avaliacao c WHERE c.modelo_id = m.id) AS total_ciclos
             FROM pessoas_modelos_avaliacao m
             WHERE m.empresa_id = :eid AND $scope
             ORDER BY m.ativo DESC, m.nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('empresa_id', $params, 'pmf');
        $stmt = $this->db->prepare("SELECT * FROM pessoas_modelos_avaliacao WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Busca o modelo garantindo que pertence exatamente à empresa informada (não só ao tenant geral). */
    public function findForEmpresa(int $id, int $empresaId): ?array
    {
        $modelo = $this->find($id);
        if (!$modelo || (int)$modelo['empresa_id'] !== $empresaId) {
            return null;
        }
        return $modelo;
    }

    public function create(array $data): int
    {
        $empresaId = (int)$this->normalizeScopedClienteId((int)($data['empresa_id'] ?? 0));
        if ($empresaId <= 0 || !$this->canAccessClienteId($empresaId)) {
            return 0;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_modelos_avaliacao (empresa_id, nome, descricao, ativo, created_by)
             VALUES (:empresa_id, :nome, :descricao, 1, :created_by)'
        );
        $stmt->execute([
            'empresa_id' => $empresaId,
            'nome' => trim((string)($data['nome'] ?? '')),
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $empresaId, array $data): bool
    {
        if ($this->findForEmpresa($id, $empresaId) === null) {
            return false;
        }
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('empresa_id', $params, 'pmu');
        $params['nome'] = trim((string)($data['nome'] ?? ''));
        $params['descricao'] = ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null;
        $stmt = $this->db->prepare("UPDATE pessoas_modelos_avaliacao SET nome = :nome, descricao = :descricao WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function toggleAtivo(int $id, int $empresaId): bool
    {
        $modelo = $this->findForEmpresa($id, $empresaId);
        if ($modelo === null) {
            return false;
        }
        $novoAtivo = (int)$modelo['ativo'] === 1 ? 0 : 1;
        $params = ['id' => $id, 'ativo' => $novoAtivo];
        $scope = $this->tenantInCondition('empresa_id', $params, 'pmta');
        $stmt = $this->db->prepare("UPDATE pessoas_modelos_avaliacao SET ativo = :ativo WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    /** true se o modelo já é usado por pelo menos um ciclo (histórico) - bloqueia mudanças estruturais destrutivas. */
    public function isUsedByCiclo(int $modeloId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM pessoas_ciclos_avaliacao WHERE modelo_id = :id');
        $stmt->execute(['id' => $modeloId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ---------------------------------------------------------------
    // Grupos
    // ---------------------------------------------------------------

    public function listGroups(int $modeloId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_modelos_grupos WHERE modelo_id = :mid ORDER BY ordem ASC, id ASC');
        $stmt->execute(['mid' => $modeloId]);
        return $stmt->fetchAll();
    }

    public function findGroup(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_modelos_grupos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createGroup(int $modeloId, array $data): int
    {
        $nextOrdem = (int)$this->db->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM pessoas_modelos_grupos WHERE modelo_id = ' . (int)$modeloId)->fetchColumn();
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_modelos_grupos (modelo_id, nome, descricao, ordem) VALUES (:mid, :nome, :descricao, :ordem)'
        );
        $stmt->execute([
            'mid' => $modeloId,
            'nome' => trim((string)($data['nome'] ?? '')),
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'ordem' => isset($data['ordem']) ? (int)$data['ordem'] : $nextOrdem,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateGroup(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE pessoas_modelos_grupos SET nome = :nome, descricao = :descricao, ordem = :ordem WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $id,
            'nome' => trim((string)($data['nome'] ?? '')),
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'ordem' => (int)($data['ordem'] ?? 0),
        ]);
    }

    public function deleteGroup(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pessoas_modelos_grupos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ---------------------------------------------------------------
    // Perguntas
    // ---------------------------------------------------------------

    public function listQuestions(int $grupoId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_modelos_perguntas WHERE grupo_id = :gid ORDER BY ordem ASC, id ASC');
        $stmt->execute(['gid' => $grupoId]);
        return $stmt->fetchAll();
    }

    public function findQuestion(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_modelos_perguntas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createQuestion(int $grupoId, array $data): int
    {
        $nextOrdem = (int)$this->db->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM pessoas_modelos_perguntas WHERE grupo_id = ' . (int)$grupoId)->fetchColumn();
        $peso = (float)($data['peso'] ?? 1.0);
        if ($peso <= 0) {
            $peso = 1.0;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_modelos_perguntas (grupo_id, pergunta, orientacao, peso, ordem, obrigatoria, ativo)
             VALUES (:gid, :pergunta, :orientacao, :peso, :ordem, :obrigatoria, 1)'
        );
        $stmt->execute([
            'gid' => $grupoId,
            'pergunta' => trim((string)($data['pergunta'] ?? '')),
            'orientacao' => ($data['orientacao'] ?? null) !== null && trim((string)$data['orientacao']) !== '' ? trim((string)$data['orientacao']) : null,
            'peso' => number_format($peso, 2, '.', ''),
            'ordem' => isset($data['ordem']) ? (int)$data['ordem'] : $nextOrdem,
            'obrigatoria' => !empty($data['obrigatoria']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateQuestion(int $id, array $data): bool
    {
        $peso = (float)($data['peso'] ?? 1.0);
        if ($peso <= 0) {
            $peso = 1.0;
        }
        $stmt = $this->db->prepare(
            'UPDATE pessoas_modelos_perguntas
             SET pergunta = :pergunta, orientacao = :orientacao, peso = :peso, ordem = :ordem, obrigatoria = :obrigatoria
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $id,
            'pergunta' => trim((string)($data['pergunta'] ?? '')),
            'orientacao' => ($data['orientacao'] ?? null) !== null && trim((string)$data['orientacao']) !== '' ? trim((string)$data['orientacao']) : null,
            'peso' => number_format($peso, 2, '.', ''),
            'ordem' => (int)($data['ordem'] ?? 0),
            'obrigatoria' => !empty($data['obrigatoria']) ? 1 : 0,
        ]);
    }

    public function deleteQuestion(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pessoas_modelos_perguntas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Estrutura completa do modelo (grupos com suas perguntas, ordenados),
     * usada tanto para a tela de edição do modelo quanto para gerar o
     * snapshot ao iniciar uma avaliação.
     *
     * @return array<int,array{group:array,perguntas:array}>
     */
    public function fullStructure(int $modeloId): array
    {
        $groups = $this->listGroups($modeloId);
        $structure = [];
        foreach ($groups as $group) {
            $structure[] = [
                'group' => $group,
                'perguntas' => $this->listQuestions((int)$group['id']),
            ];
        }
        return $structure;
    }
}

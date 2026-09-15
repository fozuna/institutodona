<?php
namespace App\Models;

/**
 * Pilar de Pessoas - Avaliação de Desempenho: Ciclo de Avaliação e seus
 * Participantes (pessoas_ciclos_avaliacao / pessoas_ciclo_participantes).
 */
class PessoaCicloAvaliacaoModel extends BaseModel
{
    private const STATUS_RASCUNHO = 'rascunho';
    private const STATUS_ABERTO = 'aberto';
    private const STATUS_ENCERRADO = 'encerrado';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ABERTO => 'Aberto',
            self::STATUS_ENCERRADO => 'Encerrado',
        ];
    }

    public function listByEmpresa(int $empresaId): array
    {
        $params = ['eid' => $empresaId];
        $scope = $this->tenantInCondition('c.empresa_id', $params, 'pcle');
        $stmt = $this->db->prepare(
            "SELECT c.*, m.nome AS modelo_nome,
                    (SELECT COUNT(*) FROM pessoas_ciclo_participantes p WHERE p.ciclo_id = c.id) AS total_participantes,
                    (SELECT COUNT(*) FROM pessoas_avaliacoes a WHERE a.ciclo_id = c.id AND a.status = 'finalizada') AS total_finalizadas
             FROM pessoas_ciclos_avaliacao c
             JOIN pessoas_modelos_avaliacao m ON m.id = c.modelo_id
             WHERE c.empresa_id = :eid AND $scope
             ORDER BY c.created_at DESC, c.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('c.empresa_id', $params, 'pcf');
        $stmt = $this->db->prepare(
            "SELECT c.*, m.nome AS modelo_nome
             FROM pessoas_ciclos_avaliacao c
             JOIN pessoas_modelos_avaliacao m ON m.id = c.modelo_id
             WHERE c.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForEmpresa(int $id, int $empresaId): ?array
    {
        $ciclo = $this->find($id);
        if (!$ciclo || (int)$ciclo['empresa_id'] !== $empresaId) {
            return null;
        }
        return $ciclo;
    }

    public function create(array $data, PessoaModeloAvaliacaoModel $modelos): int
    {
        $empresaId = (int)$this->normalizeScopedClienteId((int)($data['empresa_id'] ?? 0));
        if ($empresaId <= 0 || !$this->canAccessClienteId($empresaId)) {
            return 0;
        }
        $modeloId = (int)($data['modelo_id'] ?? 0);
        if ($modelos->findForEmpresa($modeloId, $empresaId) === null) {
            return 0;
        }
        $nome = trim((string)($data['nome'] ?? ''));
        if ($nome === '') {
            return 0;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_ciclos_avaliacao (empresa_id, modelo_id, nome, descricao, data_inicio, data_fim, status, created_by)
             VALUES (:empresa_id, :modelo_id, :nome, :descricao, :data_inicio, :data_fim, :status, :created_by)'
        );
        $stmt->execute([
            'empresa_id' => $empresaId,
            'modelo_id' => $modeloId,
            'nome' => $nome,
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
            'data_fim' => !empty($data['data_fim']) ? $data['data_fim'] : null,
            'status' => self::STATUS_RASCUNHO,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Só permite editar dados básicos enquanto o ciclo está em rascunho. */
    public function update(int $id, int $empresaId, array $data, PessoaModeloAvaliacaoModel $modelos): bool
    {
        $ciclo = $this->findForEmpresa($id, $empresaId);
        if ($ciclo === null || $ciclo['status'] !== self::STATUS_RASCUNHO) {
            return false;
        }
        $modeloId = (int)($data['modelo_id'] ?? $ciclo['modelo_id']);
        if ($modelos->findForEmpresa($modeloId, $empresaId) === null) {
            return false;
        }
        $nome = trim((string)($data['nome'] ?? ''));
        if ($nome === '') {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE pessoas_ciclos_avaliacao
             SET modelo_id = :modelo_id, nome = :nome, descricao = :descricao, data_inicio = :data_inicio, data_fim = :data_fim
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $id,
            'modelo_id' => $modeloId,
            'nome' => $nome,
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
            'data_fim' => !empty($data['data_fim']) ? $data['data_fim'] : null,
        ]);
    }

    public function abrir(int $id, int $empresaId): bool
    {
        $ciclo = $this->findForEmpresa($id, $empresaId);
        if ($ciclo === null || $ciclo['status'] !== self::STATUS_RASCUNHO) {
            return false;
        }
        if ($this->countParticipants($id) <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_ciclos_avaliacao SET status = '" . self::STATUS_ABERTO . "' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function encerrar(int $id, int $empresaId): bool
    {
        $ciclo = $this->findForEmpresa($id, $empresaId);
        if ($ciclo === null || $ciclo['status'] !== self::STATUS_ABERTO) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_ciclos_avaliacao SET status = '" . self::STATUS_ENCERRADO . "' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // ---------------------------------------------------------------
    // Participantes
    // ---------------------------------------------------------------

    public function countParticipants(int $cicloId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM pessoas_ciclo_participantes WHERE ciclo_id = :cid');
        $stmt->execute(['cid' => $cicloId]);
        return (int)$stmt->fetchColumn();
    }

    public function listParticipants(int $cicloId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.colaborador_id, col.nome, col.email, d.nome AS departamento, s.nome AS setor, f.nome AS funcao,
                    a.id AS avaliacao_id, a.status AS avaliacao_status, a.resultado
             FROM pessoas_ciclo_participantes p
             JOIN colaboradores col ON col.id = p.colaborador_id
             JOIN funcoes f ON f.id = col.funcao_id
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             LEFT JOIN pessoas_avaliacoes a ON a.ciclo_id = p.ciclo_id AND a.colaborador_id = p.colaborador_id
             WHERE p.ciclo_id = :cid
             ORDER BY d.nome, s.nome, f.nome, col.nome"
        );
        $stmt->execute(['cid' => $cicloId]);
        return $stmt->fetchAll();
    }

    /**
     * Substitui integralmente a lista de participantes do ciclo (só permitido
     * em rascunho). Cada colaborador é validado contra a MESMA empresa do
     * ciclo (não apenas o tenant geral) antes de ser vinculado - nenhum id
     * manipulado de outra empresa entra na lista.
     *
     * @return array{ok:bool, added:int, ignored:int}
     */
    public function replaceParticipants(int $cicloId, int $empresaId, array $colaboradorIds, ColaboradorModel $colaboradores): array
    {
        $ciclo = $this->findForEmpresa($cicloId, $empresaId);
        if ($ciclo === null || $ciclo['status'] !== self::STATUS_RASCUNHO) {
            return ['ok' => false, 'added' => 0, 'ignored' => 0];
        }
        $requested = array_values(array_unique(array_filter(array_map('intval', $colaboradorIds), static fn(int $v): bool => $v > 0)));
        $valid = [];
        foreach ($requested as $colaboradorId) {
            $colaborador = $colaboradores->find($colaboradorId);
            if ($colaborador && (int)$colaborador['cliente_id'] === $empresaId) {
                $valid[] = $colaboradorId;
            }
        }
        $ignored = count($requested) - count($valid);

        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM pessoas_ciclo_participantes WHERE ciclo_id = :cid')->execute(['cid' => $cicloId]);
            if (!empty($valid)) {
                $stmt = $this->db->prepare('INSERT INTO pessoas_ciclo_participantes (ciclo_id, colaborador_id) VALUES (:cid, :colid)');
                foreach ($valid as $colaboradorId) {
                    $stmt->execute(['cid' => $cicloId, 'colid' => $colaboradorId]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'added' => 0, 'ignored' => 0];
        }
        return ['ok' => true, 'added' => count($valid), 'ignored' => $ignored];
    }
}

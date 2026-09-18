<?php
namespace App\Models;

/**
 * Pilar de Pessoas, Sprint 02 - Ação de Melhoria (pessoas_acoes_melhoria):
 * normalmente nasce de um GAP, mas pode existir isoladamente para o
 * colaborador. responsavel_usuario_id sempre aponta para um usuário já
 * existente do sistema (nunca um cadastro paralelo).
 */
class PessoaAcaoMelhoriaModel extends BaseModel
{
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_EM_ANDAMENTO = 'em_andamento';
    private const STATUS_CONCLUIDA = 'concluida';
    private const STATUS_CANCELADA = 'cancelada';

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('ac.empresa_id', $params, 'pamf');
        $stmt = $this->db->prepare(
            "SELECT ac.*, col.nome AS colaborador_nome, u.nome AS responsavel_nome
             FROM pessoas_acoes_melhoria ac
             JOIN colaboradores col ON col.id = ac.colaborador_id
             LEFT JOIN usuarios u ON u.id = ac.responsavel_usuario_id
             WHERE ac.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByColaborador(int $colaboradorId, int $empresaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ac.*, u.nome AS responsavel_nome
             FROM pessoas_acoes_melhoria ac
             LEFT JOIN usuarios u ON u.id = ac.responsavel_usuario_id
             WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid
             ORDER BY ac.created_at DESC, ac.id DESC"
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function listByGap(int $gapId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ac.*, u.nome AS responsavel_nome
             FROM pessoas_acoes_melhoria ac
             LEFT JOIN usuarios u ON u.id = ac.responsavel_usuario_id
             WHERE ac.gap_id = :gid
             ORDER BY ac.created_at DESC, ac.id DESC"
        );
        $stmt->execute(['gid' => $gapId]);
        return $stmt->fetchAll();
    }

    /**
     * Usuários elegíveis para responder pela ação: Instituto (acesso
     * global) + usuários vinculados diretamente à empresa (usuarios.id_cliente)
     * + usuários com vínculo explícito via usuario_empresas (mesmo
     * mecanismo já usado para escopo de Consultor) - nenhum cadastro
     * paralelo de responsáveis, só reaproveita usuários já existentes
     * dentro do escopo real da empresa.
     */
    public function usuariosResponsaveisDisponiveis(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id, u.nome, u.tipo_acesso
             FROM usuarios u
             WHERE u.tipo_acesso = 'instituto'
                OR u.id_cliente = :eid1
                OR u.id IN (SELECT usuario_id FROM usuario_empresas WHERE cliente_id = :eid2 AND permitido = 1)
             ORDER BY u.nome"
        );
        $stmt->execute(['eid1' => $empresaId, 'eid2' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * gap_id/avaliacao_id, quando informados, são validados contra o MESMO
     * colaborador/empresa. responsavel_usuario_id só é aceito se realmente
     * fizer parte do escopo de usuários elegíveis da empresa - caso
     * contrário fica NULL (created_by continua registrando quem criou).
     */
    public function create(int $empresaId, int $colaboradorId, array $data, ColaboradorModel $colaboradores, PessoaGapModel $gaps, PessoaAvaliacaoModel $avaliacoes, int $createdBy): int
    {
        $colaborador = $colaboradores->find($colaboradorId);
        if (!$colaborador || (int)$colaborador['cliente_id'] !== $empresaId) {
            return 0;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            return 0;
        }

        $gapId = (int)($data['gap_id'] ?? 0);
        if ($gapId > 0) {
            $gap = $gaps->findForColaborador($gapId, $colaboradorId, $empresaId);
            if ($gap === null) {
                $gapId = 0;
            }
        }
        $avaliacaoId = (int)($data['avaliacao_id'] ?? 0);
        if ($avaliacaoId > 0) {
            $avaliacao = $avaliacoes->find($avaliacaoId);
            if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId || (int)$avaliacao['colaborador_id'] !== $colaboradorId) {
                $avaliacaoId = 0;
            }
        }

        $responsavelId = (int)($data['responsavel_usuario_id'] ?? 0);
        if ($responsavelId > 0) {
            $elegiveis = array_column($this->usuariosResponsaveisDisponiveis($empresaId), 'id');
            if (!in_array($responsavelId, $elegiveis, true)) {
                $responsavelId = 0;
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_acoes_melhoria
                (empresa_id, colaborador_id, gap_id, avaliacao_id, titulo, descricao, responsavel_usuario_id, data_inicio, prazo, status, created_by)
             VALUES (:eid, :cid, :gid, :aid, :titulo, :descricao, :resp, :data_inicio, :prazo, :status, :created_by)'
        );
        $stmt->execute([
            'eid' => $empresaId,
            'cid' => $colaboradorId,
            'gid' => $gapId > 0 ? $gapId : null,
            'aid' => $avaliacaoId > 0 ? $avaliacaoId : null,
            'titulo' => $titulo,
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'resp' => $responsavelId > 0 ? $responsavelId : null,
            'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
            'prazo' => !empty($data['prazo']) ? $data['prazo'] : null,
            'status' => self::STATUS_PENDENTE,
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function marcarEmAndamento(int $id, int $empresaId): bool
    {
        $acao = $this->find($id);
        if (!$acao || (int)$acao['empresa_id'] !== $empresaId || $acao['status'] !== self::STATUS_PENDENTE) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_acoes_melhoria SET status = '" . self::STATUS_EM_ANDAMENTO . "' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function concluir(int $id, int $empresaId, string $conclusao): bool
    {
        $acao = $this->find($id);
        if (!$acao || (int)$acao['empresa_id'] !== $empresaId || !in_array($acao['status'], [self::STATUS_PENDENTE, self::STATUS_EM_ANDAMENTO], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE pessoas_acoes_melhoria SET status = '" . self::STATUS_CONCLUIDA . "', conclusao = :conclusao, concluido_em = NOW() WHERE id = :id"
        );
        return $stmt->execute(['id' => $id, 'conclusao' => trim($conclusao) !== '' ? trim($conclusao) : null]);
    }

    public function cancelar(int $id, int $empresaId): bool
    {
        $acao = $this->find($id);
        if (!$acao || (int)$acao['empresa_id'] !== $empresaId || !in_array($acao['status'], [self::STATUS_PENDENTE, self::STATUS_EM_ANDAMENTO], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_acoes_melhoria SET status = '" . self::STATUS_CANCELADA . "' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

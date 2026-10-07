<?php
namespace App\Models;

/**
 * Pilar de Pessoas, Sprint 02 - Feedback (pessoas_feedbacks): positivo ou
 * de melhoria, independente de avaliação/GAP (pode ser registrado a
 * qualquer momento no histórico do colaborador) ou relacionado a uma
 * avaliação/GAP específicos.
 */
class PessoaFeedbackModel extends BaseModel
{
    private const TIPOS_VALIDOS = ['positivo', 'melhoria'];

    /**
     * Sprint 05.1: junta empresa/autor/GAP/avaliação relacionados (mesmo
     * estilo de PessoaGapModel::find()) - usado tanto pela tela de detalhe
     * quanto por qualquer lugar que precise do feedback já enriquecido.
     */
    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('f.empresa_id', $params, 'pff');
        $stmt = $this->db->prepare(
            "SELECT f.*, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome,
                    u.nome AS registrado_por_nome, g.titulo AS gap_titulo,
                    a.status AS avaliacao_status, cic.nome AS ciclo_nome
             FROM pessoas_feedbacks f
             JOIN colaboradores col ON col.id = f.colaborador_id
             JOIN clientes c ON c.id = f.empresa_id
             LEFT JOIN usuarios u ON u.id = f.registrado_por
             LEFT JOIN pessoas_gaps g ON g.id = f.gap_id
             LEFT JOIN pessoas_avaliacoes a ON a.id = f.avaliacao_id
             LEFT JOIN pessoas_ciclos_avaliacao cic ON cic.id = a.ciclo_id
             WHERE f.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Autoria (registrado_por_nome) incluída - já persistida desde a Sprint 02, só não era exibida. */
    public function listByColaborador(int $colaboradorId, int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, u.nome AS registrado_por_nome
             FROM pessoas_feedbacks f
             LEFT JOIN usuarios u ON u.id = f.registrado_por
             WHERE f.colaborador_id = :cid AND f.empresa_id = :eid
             ORDER BY f.data_feedback DESC, f.id DESC'
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * Cria um feedback (positivo ou de melhoria). avaliacao_id e gap_id são
     * opcionais - quando informados, são validados contra o MESMO
     * colaborador/empresa antes de serem gravados (nunca confia no id cru
     * do formulário).
     */
    public function create(int $empresaId, int $colaboradorId, array $data, ColaboradorModel $colaboradores, PessoaAvaliacaoModel $avaliacoes, PessoaGapModel $gaps, int $registradoPor): int
    {
        $colaborador = $colaboradores->find($colaboradorId);
        if (!$colaborador || (int)$colaborador['cliente_id'] !== $empresaId) {
            return 0;
        }
        $tipo = (string)($data['tipo'] ?? '');
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return 0;
        }
        $titulo = mb_substr(trim((string)($data['titulo'] ?? '')), 0, 255);
        $descricao = mb_substr(trim((string)($data['descricao'] ?? '')), 0, 2000);
        if ($titulo === '' || $descricao === '') {
            return 0;
        }

        $avaliacaoId = (int)($data['avaliacao_id'] ?? 0);
        if ($avaliacaoId > 0) {
            $avaliacao = $avaliacoes->find($avaliacaoId);
            if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId || (int)$avaliacao['colaborador_id'] !== $colaboradorId) {
                $avaliacaoId = 0;
            }
        }
        $gapId = (int)($data['gap_id'] ?? 0);
        if ($gapId > 0) {
            $gap = $gaps->findForColaborador($gapId, $colaboradorId, $empresaId);
            if ($gap === null) {
                $gapId = 0;
            }
        }

        $dataFeedback = trim((string)($data['data_feedback'] ?? ''));
        if ($dataFeedback === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFeedback)) {
            $dataFeedback = date('Y-m-d');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_feedbacks (empresa_id, colaborador_id, avaliacao_id, gap_id, tipo, titulo, descricao, data_feedback, registrado_por)
             VALUES (:eid, :cid, :aid, :gid, :tipo, :titulo, :descricao, :data_feedback, :registrado_por)'
        );
        $stmt->execute([
            'eid' => $empresaId,
            'cid' => $colaboradorId,
            'aid' => $avaliacaoId > 0 ? $avaliacaoId : null,
            'gid' => $gapId > 0 ? $gapId : null,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'data_feedback' => $dataFeedback,
            'registrado_por' => $registradoPor > 0 ? $registradoPor : null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}

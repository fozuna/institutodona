<?php
namespace App\Models;

/**
 * Pilar de Pessoas, Sprint 02 - GAP (pessoas_gaps): diferença relevante
 * entre desempenho observado e esperado, nascida de uma resposta de
 * avaliação (nota <= PessoasGestaoConfig::POTENCIAL_GAP_LIMITE) ou
 * registrada manualmente.
 */
class PessoaGapModel extends BaseModel
{
    private const ORIGEM_AVALIACAO = 'avaliacao';
    private const ORIGEM_MANUAL = 'manual';
    private const STATUS_ABERTO = 'aberto';
    private const STATUS_EM_TRATAMENTO = 'em_tratamento';
    private const STATUS_RESOLVIDO = 'resolvido';

    private const PRIORIDADES_VALIDAS = ['baixa', 'media', 'alta'];

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('g.empresa_id', $params, 'pgf');
        $stmt = $this->db->prepare(
            "SELECT g.*, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome,
                    a.ciclo_id, cic.nome AS ciclo_nome,
                    r.pergunta_snapshot, r.grupo_nome_snapshot, r.resposta AS nota
             FROM pessoas_gaps g
             JOIN colaboradores col ON col.id = g.colaborador_id
             JOIN clientes c ON c.id = g.empresa_id
             LEFT JOIN pessoas_avaliacoes a ON a.id = g.avaliacao_id
             LEFT JOIN pessoas_ciclos_avaliacao cic ON cic.id = a.ciclo_id
             LEFT JOIN pessoas_avaliacao_respostas r ON r.id = g.resposta_id
             WHERE g.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForColaborador(int $id, int $colaboradorId, int $empresaId): ?array
    {
        $gap = $this->find($id);
        if (!$gap || (int)$gap['colaborador_id'] !== $colaboradorId || (int)$gap['empresa_id'] !== $empresaId) {
            return null;
        }
        return $gap;
    }

    public function findByResposta(int $respostaId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_gaps WHERE resposta_id = :rid');
        $stmt->execute(['rid' => $respostaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByColaborador(int $colaboradorId, int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pessoas_gaps WHERE colaborador_id = :cid AND empresa_id = :eid ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    private function normalizePrioridade($value): string
    {
        $value = (string)$value;
        return in_array($value, self::PRIORIDADES_VALIDAS, true) ? $value : 'media';
    }

    /**
     * GAP manual: sem avaliação/resposta associada. Colaborador precisa
     * pertencer à mesma empresa (integridade validada aqui, não confia no
     * formulário).
     */
    public function createManual(int $empresaId, int $colaboradorId, array $data, ColaboradorModel $colaboradores, int $createdBy): int
    {
        $colaborador = $colaboradores->find($colaboradorId);
        if (!$colaborador || (int)$colaborador['cliente_id'] !== $empresaId) {
            return 0;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            return 0;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_gaps (empresa_id, colaborador_id, avaliacao_id, resposta_id, titulo, descricao, origem, prioridade, status, created_by)
             VALUES (:eid, :cid, NULL, NULL, :titulo, :descricao, :origem, :prioridade, :status, :created_by)'
        );
        $stmt->execute([
            'eid' => $empresaId,
            'cid' => $colaboradorId,
            'titulo' => $titulo,
            'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
            'origem' => self::ORIGEM_MANUAL,
            'prioridade' => $this->normalizePrioridade($data['prioridade'] ?? 'media'),
            'status' => self::STATUS_ABERTO,
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * GAP a partir de uma resposta de avaliação. Valida que a avaliação
     * pertence ao colaborador/empresa informados e que a resposta pertence
     * exatamente àquela avaliação (nunca confia nos ids recebidos isolados).
     * Idempotente por design: se já existir GAP para essa resposta, retorna
     * o id do GAP já existente (não cria duplicado, não lança erro) - o
     * Controller decide como comunicar "GAP já registrado" ao usuário.
     *
     * @return array{id:int, already_existed:bool}
     */
    public function createFromResposta(int $empresaId, int $colaboradorId, int $avaliacaoId, int $respostaId, array $data, PessoaAvaliacaoModel $avaliacoes, int $createdBy): array
    {
        // Integridade ANTES de qualquer atalho de idempotência: a avaliação
        // precisa pertencer exatamente ao colaborador/empresa informados, e a
        // resposta precisa pertencer exatamente àquela avaliação. Checar
        // "já existe GAP?" antes disso vazaria confirmação/id de um GAP para
        // quem manipulou colaborador_id/avaliacao_id sem ter relação real com
        // a resposta (achado durante os testes desta Sprint).
        $avaliacao = $avaliacoes->find($avaliacaoId);
        if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId || (int)$avaliacao['colaborador_id'] !== $colaboradorId) {
            return ['id' => 0, 'already_existed' => false];
        }
        $respostas = $avaliacoes->listRespostas($avaliacaoId);
        $respostaValida = null;
        foreach ($respostas as $r) {
            if ((int)$r['id'] === $respostaId) {
                $respostaValida = $r;
                break;
            }
        }
        if ($respostaValida === null) {
            return ['id' => 0, 'already_existed' => false];
        }

        $existing = $this->findByResposta($respostaId);
        if ($existing !== null) {
            return ['id' => (int)$existing['id'], 'already_existed' => true];
        }

        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            $titulo = mb_substr((string)$respostaValida['pergunta_snapshot'], 0, 255);
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO pessoas_gaps (empresa_id, colaborador_id, avaliacao_id, resposta_id, titulo, descricao, origem, prioridade, status, created_by)
                 VALUES (:eid, :cid, :aid, :rid, :titulo, :descricao, :origem, :prioridade, :status, :created_by)'
            );
            $stmt->execute([
                'eid' => $empresaId,
                'cid' => $colaboradorId,
                'aid' => $avaliacaoId,
                'rid' => $respostaId,
                'titulo' => $titulo,
                'descricao' => ($data['descricao'] ?? null) !== null && trim((string)$data['descricao']) !== '' ? trim((string)$data['descricao']) : null,
                'origem' => self::ORIGEM_AVALIACAO,
                'prioridade' => $this->normalizePrioridade($data['prioridade'] ?? 'media'),
                'status' => self::STATUS_ABERTO,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
            return ['id' => (int)$this->db->lastInsertId(), 'already_existed' => false];
        } catch (\Throwable $e) {
            // Corrida rara: duas requisições simultâneas tentando registrar o
            // mesmo GAP - a UNIQUE KEY do banco barra a segunda; devolve o
            // registro que "venceu" em vez de propagar o erro.
            $existing = $this->findByResposta($respostaId);
            if ($existing !== null) {
                return ['id' => (int)$existing['id'], 'already_existed' => true];
            }
            return ['id' => 0, 'already_existed' => false];
        }
    }

    /**
     * aberto -> em_tratamento -> resolvido, ou aberto -> resolvido direto.
     * Resolvido não pode ser reaberto nesta Sprint.
     */
    public function updateStatus(int $id, int $empresaId, string $novoStatus): bool
    {
        $gap = $this->find($id);
        if (!$gap || (int)$gap['empresa_id'] !== $empresaId) {
            return false;
        }
        $atual = (string)$gap['status'];
        $permitido = match ($atual) {
            self::STATUS_ABERTO => in_array($novoStatus, [self::STATUS_EM_TRATAMENTO, self::STATUS_RESOLVIDO], true),
            self::STATUS_EM_TRATAMENTO => $novoStatus === self::STATUS_RESOLVIDO,
            default => false,
        };
        if (!$permitido) {
            return false;
        }
        $params = ['id' => $id, 'status' => $novoStatus];
        if ($novoStatus === self::STATUS_RESOLVIDO) {
            $stmt = $this->db->prepare('UPDATE pessoas_gaps SET status = :status, resolvido_em = NOW() WHERE id = :id');
        } else {
            $stmt = $this->db->prepare('UPDATE pessoas_gaps SET status = :status WHERE id = :id');
        }
        return $stmt->execute($params);
    }

    public static function statusLabels(): array
    {
        return \App\Core\PessoasGestaoConfig::gapStatusLabels();
    }
}

<?php
namespace App\Models;

use App\Core\PessoasAvaliacaoScale;

/**
 * Pilar de Pessoas - Avaliação de Desempenho: a Avaliação individual de um
 * participante dentro de um Ciclo, e suas respostas com snapshot histórico
 * (pessoas_avaliacoes / pessoas_avaliacao_respostas).
 */
class PessoaAvaliacaoModel extends BaseModel
{
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_EM_ANDAMENTO = 'em_andamento';
    private const STATUS_FINALIZADA = 'finalizada';

    /**
     * Garante que exista exatamente uma linha pessoas_avaliacoes (status
     * pendente) para cada colaborador atualmente participante do ciclo, e
     * remove as linhas "pendente" de quem deixou de ser participante -
     * nunca toca em avaliações já em_andamento/finalizada (histórico
     * preservado mesmo que a lista de participantes mude).
     */
    public function ensureForParticipants(int $cicloId, int $empresaId, array $colaboradorIds): void
    {
        $colaboradorIds = array_values(array_unique(array_filter(array_map('intval', $colaboradorIds), static fn(int $v): bool => $v > 0)));

        $stmt = $this->db->prepare('SELECT colaborador_id, status FROM pessoas_avaliacoes WHERE ciclo_id = :cid');
        $stmt->execute(['cid' => $cicloId]);
        $existing = $stmt->fetchAll();
        $existingIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $existing);

        $toCreate = array_diff($colaboradorIds, $existingIds);
        $toRemove = [];
        foreach ($existing as $row) {
            if (!in_array((int)$row['colaborador_id'], $colaboradorIds, true) && $row['status'] === self::STATUS_PENDENTE) {
                $toRemove[] = (int)$row['colaborador_id'];
            }
        }

        if (!empty($toCreate)) {
            $insert = $this->db->prepare(
                'INSERT INTO pessoas_avaliacoes (empresa_id, ciclo_id, colaborador_id, status) VALUES (:eid, :cid, :colid, :status)'
            );
            foreach ($toCreate as $colaboradorId) {
                $insert->execute(['eid' => $empresaId, 'cid' => $cicloId, 'colid' => $colaboradorId, 'status' => self::STATUS_PENDENTE]);
            }
        }
        if (!empty($toRemove)) {
            $placeholders = implode(',', array_fill(0, count($toRemove), '?'));
            $delete = $this->db->prepare(
                "DELETE FROM pessoas_avaliacoes WHERE ciclo_id = ? AND status = '" . self::STATUS_PENDENTE . "' AND colaborador_id IN ($placeholders)"
            );
            $delete->execute(array_merge([$cicloId], $toRemove));
        }
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('a.empresa_id', $params, 'paf');
        $stmt = $this->db->prepare(
            "SELECT a.*, c.nome AS ciclo_nome, c.status AS ciclo_status, m.nome AS modelo_nome,
                    col.nome AS colaborador_nome, d.nome AS departamento, s.nome AS setor, f.nome AS funcao,
                    u.nome AS avaliador_nome
             FROM pessoas_avaliacoes a
             JOIN pessoas_ciclos_avaliacao c ON c.id = a.ciclo_id
             JOIN pessoas_modelos_avaliacao m ON m.id = c.modelo_id
             JOIN colaboradores col ON col.id = a.colaborador_id
             JOIN funcoes f ON f.id = col.funcao_id
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             LEFT JOIN usuarios u ON u.id = a.avaliador_usuario_id
             WHERE a.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Garante que a avaliação pertence exatamente ao ciclo informado (defesa contra id manipulado). */
    public function findForCiclo(int $id, int $cicloId): ?array
    {
        $avaliacao = $this->find($id);
        if (!$avaliacao || (int)$avaliacao['ciclo_id'] !== $cicloId) {
            return null;
        }
        return $avaliacao;
    }

    /**
     * pendente -> em_andamento, gera o snapshot das perguntas do modelo
     * (só na primeira vez - idempotente: chamar de novo com a avaliação já
     * em_andamento/finalizada não duplica nada, apenas retorna a avaliação
     * como está).
     */
    public function iniciar(int $id, int $empresaId, int $usuarioId, PessoaModeloAvaliacaoModel $modelos): ?array
    {
        $avaliacao = $this->find($id);
        if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId) {
            return null;
        }
        if ($avaliacao['status'] !== self::STATUS_PENDENTE) {
            // Idempotente: já iniciada (ou finalizada) - não repete a transição nem o snapshot.
            return $avaliacao;
        }

        $ciclo = $this->db->prepare('SELECT modelo_id, status FROM pessoas_ciclos_avaliacao WHERE id = :cid');
        $ciclo->execute(['cid' => $avaliacao['ciclo_id']]);
        $cicloRow = $ciclo->fetch();
        if (!$cicloRow || $cicloRow['status'] !== 'aberto') {
            return null;
        }

        $structure = $modelos->fullStructure((int)$cicloRow['modelo_id']);
        if (empty($structure)) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE pessoas_avaliacoes SET status = :status, avaliador_usuario_id = :uid, iniciado_em = NOW()
                 WHERE id = :id AND status = '" . self::STATUS_PENDENTE . "'"
            );
            $stmt->execute(['status' => self::STATUS_EM_ANDAMENTO, 'uid' => $usuarioId, 'id' => $id]);
            if ($stmt->rowCount() < 1) {
                // Outra requisição já fez a transição entre o find() e aqui - idempotência preservada.
                $this->db->rollBack();
                return $this->find($id);
            }

            $insert = $this->db->prepare(
                'INSERT INTO pessoas_avaliacao_respostas
                    (avaliacao_id, pergunta_id, grupo_nome_snapshot, grupo_ordem_snapshot, pergunta_snapshot, peso_snapshot, obrigatoria_snapshot, ordem_snapshot)
                 VALUES (:aid, :pid, :gnome, :gordem, :ptexto, :peso, :obrig, :pordem)'
            );
            foreach ($structure as $entry) {
                $group = $entry['group'];
                foreach ($entry['perguntas'] as $pergunta) {
                    if ((int)($pergunta['ativo'] ?? 1) !== 1) {
                        continue;
                    }
                    $insert->execute([
                        'aid' => $id,
                        'pid' => (int)$pergunta['id'],
                        'gnome' => (string)$group['nome'],
                        'gordem' => (int)$group['ordem'],
                        'ptexto' => (string)$pergunta['pergunta'],
                        'peso' => (string)$pergunta['peso'],
                        'obrig' => (int)$pergunta['obrigatoria'],
                        'pordem' => (int)$pergunta['ordem'],
                    ]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return null;
        }
        return $this->find($id);
    }

    /** @return array<int,array> itens de resposta (snapshot), ordenados por grupo e pergunta. */
    public function listRespostas(int $avaliacaoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pessoas_avaliacao_respostas WHERE avaliacao_id = :aid ORDER BY grupo_ordem_snapshot ASC, ordem_snapshot ASC, id ASC'
        );
        $stmt->execute(['aid' => $avaliacaoId]);
        return $stmt->fetchAll();
    }

    /** Agrupa listRespostas() por grupo_nome_snapshot, preservando a ordem. */
    public function respostasAgrupadas(int $avaliacaoId): array
    {
        $grouped = [];
        foreach ($this->listRespostas($avaliacaoId) as $item) {
            $key = $item['grupo_nome_snapshot'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $item;
        }
        return $grouped;
    }

    /**
     * Salvamento parcial: grava nota/observação de quantos itens vierem
     * preenchidos, sem exigir todos. Só permitido com a avaliação
     * em_andamento. Notas fora de 1-5 são rejeitadas item a item (o item
     * simplesmente não é salvo, sem quebrar os demais).
     *
     * @param array<int,array{resposta?:int|string|null, observacao?:string|null}> $respostas item_id => dados
     */
    public function saveAnswers(int $avaliacaoId, int $empresaId, array $respostas): bool
    {
        $avaliacao = $this->find($avaliacaoId);
        if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId || $avaliacao['status'] !== self::STATUS_EM_ANDAMENTO) {
            return false;
        }
        $itemIds = array_column($this->listRespostas($avaliacaoId), 'id');

        $stmt = $this->db->prepare(
            'UPDATE pessoas_avaliacao_respostas SET resposta = :resposta, observacao = :observacao WHERE id = :id AND avaliacao_id = :aid'
        );
        foreach ($respostas as $itemId => $payload) {
            $itemId = (int)$itemId;
            if (!in_array($itemId, $itemIds, true)) {
                continue;
            }
            $notaRaw = $payload['resposta'] ?? null;
            $nota = $notaRaw !== null && $notaRaw !== '' ? (int)$notaRaw : null;
            if ($nota !== null && !PessoasAvaliacaoScale::isValidNota($nota)) {
                continue;
            }
            $observacao = isset($payload['observacao']) ? trim((string)$payload['observacao']) : null;
            $observacao = $observacao !== '' ? $observacao : null;
            $stmt->execute(['resposta' => $nota, 'observacao' => $observacao, 'id' => $itemId, 'aid' => $avaliacaoId]);
        }
        return true;
    }

    /**
     * Finaliza a avaliação: valida obrigatórias respondidas e notas 1-5,
     * calcula o resultado geral (média ponderada) e grava tudo.
     *
     * @return array{ok:bool, errors:array<int,string>}
     */
    public function finalizar(int $avaliacaoId, int $empresaId): array
    {
        $avaliacao = $this->find($avaliacaoId);
        if (!$avaliacao || (int)$avaliacao['empresa_id'] !== $empresaId) {
            return ['ok' => false, 'errors' => ['Avaliação não encontrada.']];
        }
        if ($avaliacao['status'] === self::STATUS_FINALIZADA) {
            return ['ok' => false, 'errors' => ['Avaliação já finalizada.']];
        }
        if ($avaliacao['status'] !== self::STATUS_EM_ANDAMENTO) {
            return ['ok' => false, 'errors' => ['Avaliação precisa estar em andamento para ser finalizada.']];
        }

        $itens = $this->listRespostas($avaliacaoId);
        $errors = [];
        foreach ($itens as $item) {
            $obrigatoria = (int)$item['obrigatoria_snapshot'] === 1;
            $resposta = $item['resposta'];
            if ($obrigatoria && ($resposta === null || $resposta === '')) {
                $errors[] = 'Pergunta obrigatória sem resposta: ' . $item['pergunta_snapshot'];
                continue;
            }
            if ($resposta !== null && $resposta !== '' && !PessoasAvaliacaoScale::isValidNota((int)$resposta)) {
                $errors[] = 'Nota inválida (deve ser de 1 a 5) em: ' . $item['pergunta_snapshot'];
            }
        }
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        $weighted = array_map(static fn(array $i): array => ['resposta' => $i['resposta'], 'peso' => (float)$i['peso_snapshot']], $itens);
        $resultado = PessoasAvaliacaoScale::weightedAverage($weighted);
        if ($resultado === null) {
            return ['ok' => false, 'errors' => ['Nenhuma resposta válida para calcular o resultado.']];
        }

        $stmt = $this->db->prepare(
            "UPDATE pessoas_avaliacoes SET status = :status, resultado = :resultado, finalizado_em = NOW()
             WHERE id = :id AND status = '" . self::STATUS_EM_ANDAMENTO . "'"
        );
        $ok = $stmt->execute(['status' => self::STATUS_FINALIZADA, 'resultado' => $resultado, 'id' => $avaliacaoId]);
        return ['ok' => (bool)$ok && $stmt->rowCount() > 0, 'errors' => []];
    }

    /** Resultado por grupo, calculado em consulta (não persistido) a partir do snapshot. */
    public function resultadoPorGrupo(int $avaliacaoId): array
    {
        $resultado = [];
        foreach ($this->respostasAgrupadas($avaliacaoId) as $grupoNome => $itens) {
            $weighted = array_map(static fn(array $i): array => ['resposta' => $i['resposta'], 'peso' => (float)$i['peso_snapshot']], $itens);
            $media = PessoasAvaliacaoScale::weightedAverage($weighted);
            $resultado[] = [
                'grupo' => $grupoNome,
                'resultado' => $media,
                'classificacao' => $media !== null ? PessoasAvaliacaoScale::classification($media) : null,
            ];
        }
        return $resultado;
    }
}

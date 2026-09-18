<?php
namespace App\Models;

/**
 * Pilar de Pessoas, Sprint 03 - orquestra o desenvolvimento a partir de uma
 * Ação de Melhoria usando os módulos que já existem (Plano de Ação =
 * pdca_tasks via PlanoAcaoTaskModel; Treinamentos = treinamentos +
 * treinamento_colaboradores via TreinamentoModel). Nada é duplicado: aqui só
 * ficam os VÍNCULOS estruturais (pessoas_acao_planos, pessoas_acao_treinamentos)
 * e a Necessidade de Treinamento (conceito que não existia).
 *
 * Ordem obrigatória em toda operação (lição do bug da Sprint 02):
 * 1) autorização/tenant/propriedade -> 2) relação semântica ->
 * 3) idempotência (vínculo já existe?) -> 4) execução.
 * Os ciclos de vida de Ação, GAP, Plano e Treinamento são INDEPENDENTES:
 * nada aqui conclui/resolve nenhum deles.
 */
class PessoaDesenvolvimentoModel extends BaseModel
{
    private const PRIORIDADES = ['baixa', 'media', 'alta'];

    // ---------------------------------------------------------------
    // Plano de Ação
    // ---------------------------------------------------------------

    /**
     * Cria um Plano de Ação (pdca_tasks) a partir da Ação e registra o
     * vínculo. No máximo 1 por Ação (UNIQUE no banco). PlanoAcaoTaskModel::
     * create() executa DDL interno (ensure()), o que encerra qualquer
     * transação aberta - por isso não há transação única: a atomicidade é
     * garantida por COMPENSAÇÃO (se o vínculo falhar, o Plano recém-criado é
     * removido), sem deixar Plano órfão sem rastreabilidade.
     *
     * @return array{ok:bool, error:?string, plano_id:int, already_existed:bool}
     */
    public function encaminharPlanoAcao(int $acaoId, array $data, PessoaAcaoMelhoriaModel $acoes, PlanoAcaoTaskModel $tasks, int $userId): array
    {
        $fail = static fn(string $m): array => ['ok' => false, 'error' => $m, 'plano_id' => 0, 'already_existed' => false];

        // 1) tenant/propriedade (find() já filtra por escopo; canAccess é redundância defensiva)
        $acao = $acoes->find($acaoId);
        if (!$acao || !$this->canAccessClienteId((int)$acao['empresa_id'])) {
            return $fail('Ação de Melhoria não encontrada.');
        }
        $empresaId = (int)$acao['empresa_id'];
        // 2) relação semântica: a Ação precisa estar ativa
        if (!in_array($acao['status'], ['pendente', 'em_andamento'], true)) {
            return $fail('Só é possível encaminhar Ações pendentes ou em andamento.');
        }
        // 3) idempotência
        $existing = $this->planoDaAcao($acaoId);
        if ($existing !== null) {
            return ['ok' => true, 'error' => null, 'plano_id' => (int)$existing['plano_task_id'], 'already_existed' => true];
        }
        // 4) execução
        $titulo = trim((string)($data['titulo'] ?? '')) !== '' ? trim((string)$data['titulo']) : (string)$acao['titulo'];
        $descricaoBase = trim((string)($data['descricao'] ?? '')) !== '' ? trim((string)$data['descricao']) : trim((string)($acao['descricao'] ?? ''));
        // Só o necessário para execução: nome do colaborador + origem. GAP/feedback NÃO são copiados.
        $descricao = trim($descricaoBase . "\n\nColaborador: " . $acao['colaborador_nome'] . "\nOriginado do Pilar de Pessoas");
        $prazo = !empty($data['prazo']) ? $data['prazo'] : ($acao['prazo'] ?? null);

        $taskId = $tasks->create([
            'id_cliente' => $empresaId,
            'titulo' => mb_substr($titulo, 0, 255),
            'descricao' => $descricao,
            'prazo' => $prazo ?: null,
            'responsavel' => (string)($acao['responsavel_nome'] ?? ''),
            'status' => 'Planejado',
            'progresso' => 0,
        ]);
        if ($taskId <= 0) {
            return $fail('Não foi possível criar o Plano de Ação.');
        }
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO pessoas_acao_planos (empresa_id, acao_melhoria_id, plano_task_id, created_by) VALUES (:eid, :aid, :tid, :uid)'
            );
            $stmt->execute(['eid' => $empresaId, 'aid' => $acaoId, 'tid' => $taskId, 'uid' => $userId > 0 ? $userId : null]);
        } catch (\Throwable $e) {
            // Compensação: nunca deixa Plano órfão.
            try { $tasks->delete($taskId); } catch (\Throwable $e2) {}
            // Corrida: outra requisição venceu - devolve o vínculo dela.
            $venceu = $this->planoDaAcao($acaoId);
            if ($venceu !== null) {
                return ['ok' => true, 'error' => null, 'plano_id' => (int)$venceu['plano_task_id'], 'already_existed' => true];
            }
            return $fail('Não foi possível registrar o encaminhamento.');
        }
        return ['ok' => true, 'error' => null, 'plano_id' => $taskId, 'already_existed' => false];
    }

    public function planoDaAcao(int $acaoId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ap.*, t.titulo, t.status AS plano_status, t.prazo AS plano_prazo, t.progresso
             FROM pessoas_acao_planos ap
             JOIN pdca_tasks t ON t.id = ap.plano_task_id
             WHERE ap.acao_melhoria_id = :aid'
        );
        $stmt->execute(['aid' => $acaoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ---------------------------------------------------------------
    // Treinamento
    // ---------------------------------------------------------------

    /** Treinamentos da empresa (catálogo existente) - explícito por empresa, nunca de outra. */
    public function treinamentosDisponiveis(int $empresaId): array
    {
        $params = ['eid' => $empresaId];
        $scope = $this->tenantInCondition('COALESCE(t.cliente_id, d.cliente_id)', $params, 'ptd');
        $stmt = $this->db->prepare(
            "SELECT t.id, t.nome FROM treinamentos t JOIN departamentos d ON d.id = t.departamento_id
             WHERE COALESCE(t.cliente_id, d.cliente_id) = :eid AND $scope ORDER BY t.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Vincula a Ação a um Treinamento EXISTENTE e garante o colaborador na
     * lista do treinamento (TreinamentoModel::addParticipanteExtra - contrato
     * existente; não cria agenda/turma fictícia: fica "Aguardando
     * agendamento"). Transacional: participante + vínculo juntos ou nada.
     *
     * @return array{ok:bool, error:?string, already_existed:bool}
     */
    public function vincularTreinamento(int $acaoId, int $treinamentoId, PessoaAcaoMelhoriaModel $acoes, TreinamentoModel $treinamentos, int $userId): array
    {
        $owns = !$this->db->inTransaction();
        // find() do TreinamentoModel memoiza o ensureSchema (DDL) ANTES da transação.
        $treinamento = $treinamentos->find($treinamentoId);
        if ($owns) {
            $this->db->beginTransaction();
        }
        try {
            $r = $this->vincularTreinamentoInterno($acaoId, $treinamentoId, $treinamento, $acoes, $treinamentos, $userId);
            if ($owns) {
                $r['ok'] ? $this->db->commit() : $this->db->rollBack();
            }
            return $r;
        } catch (\Throwable $e) {
            if ($owns && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'error' => 'Não foi possível vincular o treinamento.', 'already_existed' => false];
        }
    }

    private function vincularTreinamentoInterno(int $acaoId, int $treinamentoId, ?array $treinamento, PessoaAcaoMelhoriaModel $acoes, TreinamentoModel $treinamentos, int $userId): array
    {
        $fail = static fn(string $m): array => ['ok' => false, 'error' => $m, 'already_existed' => false];
        // 1) tenant/propriedade da Ação
        $acao = $acoes->find($acaoId);
        if (!$acao || !$this->canAccessClienteId((int)$acao['empresa_id'])) {
            return $fail('Ação de Melhoria não encontrada.');
        }
        $empresaId = (int)$acao['empresa_id'];
        // 2) relação semântica: treinamento visível no escopo E da mesma empresa da Ação
        if (!$treinamento || (int)($treinamento['cliente_id'] ?? 0) !== $empresaId) {
            return $fail('Treinamento não encontrado para esta empresa.');
        }
        if (!in_array($acao['status'], ['pendente', 'em_andamento'], true)) {
            return $fail('Só é possível encaminhar Ações pendentes ou em andamento.');
        }
        // 3) idempotência
        $stmt = $this->db->prepare('SELECT id FROM pessoas_acao_treinamentos WHERE acao_melhoria_id = :aid AND treinamento_id = :tid');
        $stmt->execute(['aid' => $acaoId, 'tid' => $treinamentoId]);
        if ($stmt->fetchColumn()) {
            return ['ok' => true, 'error' => null, 'already_existed' => true];
        }
        // 4) execução: colaborador na lista do treinamento (já estar nela é ok)
        $colaboradorId = (int)$acao['colaborador_id'];
        $ja = $this->db->prepare('SELECT 1 FROM treinamento_colaboradores WHERE treinamento_id = :tid AND colaborador_id = :cid');
        $ja->execute(['tid' => $treinamentoId, 'cid' => $colaboradorId]);
        if (!$ja->fetchColumn()) {
            $add = $treinamentos->addParticipanteExtra($treinamentoId, $colaboradorId);
            if (empty($add['ok'])) {
                return $fail((string)($add['error'] ?? 'Não foi possível incluir o colaborador no treinamento.'));
            }
        }
        $ins = $this->db->prepare(
            'INSERT INTO pessoas_acao_treinamentos (empresa_id, acao_melhoria_id, treinamento_id, created_by) VALUES (:eid, :aid, :tid, :uid)'
        );
        $ins->execute(['eid' => $empresaId, 'aid' => $acaoId, 'tid' => $treinamentoId, 'uid' => $userId > 0 ? $userId : null]);
        return ['ok' => true, 'error' => null, 'already_existed' => false];
    }

    /** Treinamentos vinculados + situação derivada do módulo de Treinamentos (sem duplicar status). */
    public function treinamentosDaAcao(int $acaoId, int $colaboradorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT at.treinamento_id, t.nome, at.created_at
             FROM pessoas_acao_treinamentos at JOIN treinamentos t ON t.id = at.treinamento_id
             WHERE at.acao_melhoria_id = :aid ORDER BY at.id'
        );
        $stmt->execute(['aid' => $acaoId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['situacao'] = $this->situacaoTreinamento((int)$row['treinamento_id'], $colaboradorId);
        }
        return $rows;
    }

    /** @return array{label:string, concluido:bool, certificado:bool} */
    public function situacaoTreinamento(int $treinamentoId, int $colaboradorId): array
    {
        $roster = $this->db->prepare('SELECT status FROM treinamento_colaboradores WHERE treinamento_id = :tid AND colaborador_id = :cid');
        $roster->execute(['tid' => $treinamentoId, 'cid' => $colaboradorId]);
        $status = $roster->fetchColumn();
        $part = $this->db->prepare(
            'SELECT a.data, tp.presenca, tp.certificado_emitido
             FROM treinamento_participantes tp JOIN treinamentos_agenda a ON a.id = tp.agenda_id
             WHERE a.treinamento_id = :tid AND tp.colaborador_id = :cid ORDER BY a.data DESC LIMIT 1'
        );
        $part->execute(['tid' => $treinamentoId, 'cid' => $colaboradorId]);
        $p = $part->fetch();
        $cert = $p && (int)$p['certificado_emitido'] === 1;
        if ($status === 'concluido') {
            return ['label' => 'Concluído', 'concluido' => true, 'certificado' => $cert];
        }
        if ($p) {
            $futuro = strtotime((string)$p['data']) >= strtotime('today');
            return ['label' => $futuro ? 'Agendado' : 'Realizado — aguardando conclusão', 'concluido' => false, 'certificado' => $cert];
        }
        return ['label' => 'Aguardando agendamento', 'concluido' => false, 'certificado' => false];
    }

    // ---------------------------------------------------------------
    // Necessidade de Treinamento
    // ---------------------------------------------------------------

    /** @return array{ok:bool, error:?string, id:int, already_existed:bool} */
    public function registrarNecessidade(int $acaoId, array $data, PessoaAcaoMelhoriaModel $acoes, int $userId): array
    {
        $acao = $acoes->find($acaoId);
        if (!$acao || !$this->canAccessClienteId((int)$acao['empresa_id'])) {
            return ['ok' => false, 'error' => 'Ação de Melhoria não encontrada.', 'id' => 0, 'already_existed' => false];
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            return ['ok' => false, 'error' => 'Informe o título da necessidade.', 'id' => 0, 'already_existed' => false];
        }
        $prioridade = in_array((string)($data['prioridade'] ?? ''), self::PRIORIDADES, true) ? (string)$data['prioridade'] : 'media';
        // Idempotência: mesma necessidade pendente (mesmo título) para a mesma Ação.
        $dup = $this->db->prepare("SELECT id FROM pessoas_necessidades_treinamento WHERE acao_melhoria_id = :aid AND titulo = :t AND status = 'pendente'");
        $dup->execute(['aid' => $acaoId, 't' => $titulo]);
        if (($id = (int)$dup->fetchColumn()) > 0) {
            return ['ok' => true, 'error' => null, 'id' => $id, 'already_existed' => true];
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pessoas_necessidades_treinamento (empresa_id, colaborador_id, acao_melhoria_id, titulo, descricao, prioridade, status, created_by)
             VALUES (:eid, :cid, :aid, :t, :d, :p, \'pendente\', :uid)'
        );
        $stmt->execute([
            'eid' => (int)$acao['empresa_id'], 'cid' => (int)$acao['colaborador_id'], 'aid' => $acaoId, 't' => mb_substr($titulo, 0, 255),
            'd' => trim((string)($data['descricao'] ?? '')) !== '' ? trim((string)$data['descricao']) : null,
            'p' => $prioridade, 'uid' => $userId > 0 ? $userId : null,
        ]);
        return ['ok' => true, 'error' => null, 'id' => (int)$this->db->lastInsertId(), 'already_existed' => false];
    }

    public function findNecessidade(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('n.empresa_id', $params, 'pnf');
        $stmt = $this->db->prepare("SELECT n.* FROM pessoas_necessidades_treinamento n WHERE n.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function necessidadesDaAcao(int $acaoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT n.*, t.nome AS treinamento_nome FROM pessoas_necessidades_treinamento n
             LEFT JOIN treinamentos t ON t.id = n.treinamento_id
             WHERE n.acao_melhoria_id = :aid ORDER BY n.created_at DESC, n.id DESC'
        );
        $stmt->execute(['aid' => $acaoId]);
        return $stmt->fetchAll();
    }

    /**
     * Atende a necessidade com um Treinamento existente: vínculo Ação<->Treinamento
     * + status atendida numa ÚNICA transação (ou tudo, ou nada).
     *
     * @return array{ok:bool, error:?string}
     */
    public function atenderNecessidade(int $necessidadeId, int $treinamentoId, PessoaAcaoMelhoriaModel $acoes, TreinamentoModel $treinamentos, int $userId): array
    {
        $n = $this->findNecessidade($necessidadeId);
        if (!$n) {
            return ['ok' => false, 'error' => 'Necessidade não encontrada.'];
        }
        if ($n['status'] !== 'pendente') {
            return ['ok' => false, 'error' => 'Só necessidades pendentes podem ser atendidas.'];
        }
        $treinamento = $treinamentos->find($treinamentoId); // DDL memoizado antes da transação
        $this->db->beginTransaction();
        try {
            $r = $this->vincularTreinamentoInterno((int)$n['acao_melhoria_id'], $treinamentoId, $treinamento, $acoes, $treinamentos, $userId);
            if (!$r['ok']) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => $r['error']];
            }
            $up = $this->db->prepare(
                "UPDATE pessoas_necessidades_treinamento SET status = 'atendida', treinamento_id = :tid, atendida_em = NOW()
                 WHERE id = :id AND status = 'pendente'"
            );
            $up->execute(['tid' => $treinamentoId, 'id' => $necessidadeId]);
            if ($up->rowCount() < 1) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Necessidade já foi atendida.'];
            }
            $this->db->commit();
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'error' => 'Não foi possível atender a necessidade.'];
        }
    }

    public function cancelarNecessidade(int $necessidadeId): bool
    {
        $n = $this->findNecessidade($necessidadeId);
        if (!$n || $n['status'] !== 'pendente') {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_necessidades_treinamento SET status = 'cancelada' WHERE id = :id AND status = 'pendente'");
        return $stmt->execute(['id' => $necessidadeId]);
    }

    // ---------------------------------------------------------------
    // Consolidação (Histórico / detalhe da Ação)
    // ---------------------------------------------------------------

    /** Desenvolvimento consolidado de uma Ação + estado DERIVADO (sem coluna redundante). */
    public function desenvolvimentoDaAcao(array $acao): array
    {
        $plano = $this->planoDaAcao((int)$acao['id']);
        $treinamentos = $this->treinamentosDaAcao((int)$acao['id'], (int)$acao['colaborador_id']);
        $necessidades = $this->necessidadesDaAcao((int)$acao['id']);
        return [
            'plano' => $plano,
            'treinamentos' => $treinamentos,
            'necessidades' => $necessidades,
            'estado' => self::estadoDerivado($acao, $plano, $treinamentos, $necessidades),
        ];
    }

    public static function estadoDerivado(array $acao, ?array $plano, array $treinamentos, array $necessidades): string
    {
        if (($acao['status'] ?? '') === 'concluida') {
            return 'Desenvolvimento concluído';
        }
        $trein = !empty($treinamentos);
        $todosConcl = $trein && count(array_filter($treinamentos, static fn($t) => !empty($t['situacao']['concluido']))) === count($treinamentos);
        $algumAgendado = $trein && count(array_filter($treinamentos, static fn($t) => in_array($t['situacao']['label'], ['Agendado', 'Realizado — aguardando conclusão'], true))) > 0;
        $pendNec = count(array_filter($necessidades, static fn($n) => $n['status'] === 'pendente')) > 0;
        if ($todosConcl && $plano === null) {
            return 'Treinamento concluído';
        }
        if (($acao['status'] ?? '') === 'em_andamento' || $algumAgendado || ($plano !== null && ($plano['plano_status'] ?? '') === 'Em Andamento')) {
            return 'Desenvolvimento em andamento';
        }
        if ($trein) {
            return 'Aguardando treinamento';
        }
        if ($pendNec) {
            return 'Necessidade de treinamento registrada';
        }
        if ($plano !== null) {
            return 'Plano de Ação criado';
        }
        return 'Sem encaminhamento';
    }
}

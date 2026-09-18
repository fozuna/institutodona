<?php
namespace App\Models;

use App\Core\PessoasGestaoConfig;

/**
 * Pilar de Pessoas, Sprint 04 - PDI (Plano de Desenvolvimento Individual).
 * Camada organizadora: só relaciona GAPs e Ações de Melhoria já existentes
 * (pessoas_pdi_gaps / pessoas_pdi_objetivo_acoes) - nada é copiado. Estados de
 * PDI, Objetivo, Ação, GAP, Plano e Treinamento são independentes: nenhuma
 * operação aqui conclui/resolve entidade de outro módulo.
 *
 * Ordem em toda operação: tenant/propriedade -> relação semântica ->
 * idempotência/operação (lição do bug da Sprint 02).
 */
class PessoaPdiModel extends BaseModel
{
    private const EDITAVEIS = ['rascunho', 'ativo'];

    // ---------------------------------------------------------------
    // Consulta
    // ---------------------------------------------------------------

    private function countsSql(): string
    {
        return "(SELECT COUNT(*) FROM pessoas_pdi_objetivos o WHERE o.pdi_id = p.id AND o.status <> 'cancelado') AS objetivos_validos,
                (SELECT COUNT(*) FROM pessoas_pdi_objetivos o WHERE o.pdi_id = p.id AND o.status = 'concluido') AS objetivos_concluidos,
                (SELECT COUNT(*) FROM pessoas_pdi_objetivos o WHERE o.pdi_id = p.id AND " . PessoasGestaoConfig::objetivoVencidoSql('o') . ") AS objetivos_vencidos";
    }

    private function decorate(array $row): array
    {
        $row['progresso'] = PessoasGestaoConfig::progressoPdi((int)$row['objetivos_concluidos'], (int)$row['objetivos_validos']);
        return $row;
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('p.empresa_id', $params, 'ppf');
        $stmt = $this->db->prepare(
            "SELECT p.*, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome, {$this->countsSql()}
             FROM pessoas_pdis p
             JOIN colaboradores col ON col.id = p.colaborador_id
             JOIN clientes c ON c.id = p.empresa_id
             WHERE p.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? $this->decorate($row) : null;
    }

    public function listByColaborador(int $colaboradorId, int $empresaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, {$this->countsSql()} FROM pessoas_pdis p
             WHERE p.colaborador_id = :cid AND p.empresa_id = :eid
             ORDER BY (p.status = 'ativo') DESC, p.created_at DESC, p.id DESC"
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        return array_map(fn(array $r): array => $this->decorate($r), $stmt->fetchAll());
    }

    public function pdiAtivoDoColaborador(int $colaboradorId, int $empresaId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, {$this->countsSql()} FROM pessoas_pdis p
             WHERE p.colaborador_id = :cid AND p.empresa_id = :eid AND p.status = 'ativo' LIMIT 1"
        );
        $stmt->execute(['cid' => $colaboradorId, 'eid' => $empresaId]);
        $row = $stmt->fetch();
        return $row ? $this->decorate($row) : null;
    }

    /**
     * Listagem paginada (progresso calculado por subconsulta - sem N+1).
     * $empresaIds já validado pelo Controller; tenantInCondition() reforça no Model.
     *
     * @return array{items:array,total:int}
     */
    public function paginate(array $empresaIds, array $filters, int $page, int $per): array
    {
        $empresaIds = array_values(array_filter(array_map('intval', $empresaIds), static fn(int $v): bool => $v > 0));
        if (empty($empresaIds)) {
            return ['items' => [], 'total' => 0];
        }
        $params = [];
        $in = [];
        foreach ($empresaIds as $i => $id) {
            $params['pe' . $i] = $id;
            $in[] = ':pe' . $i;
        }
        $where = 'p.empresa_id IN (' . implode(',', $in) . ') AND ' . $this->tenantInCondition('p.empresa_id', $params, 'ppl');
        if (!empty($filters['status']) && in_array($filters['status'], ['rascunho', 'ativo', 'concluido', 'cancelado'], true)) {
            $where .= ' AND p.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where .= ' AND col.nome LIKE :q';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['colaborador_id'])) {
            $where .= ' AND p.colaborador_id = :colid';
            $params['colid'] = (int)$filters['colaborador_id'];
        }
        if (!empty($filters['inicio'])) {
            $where .= ' AND p.data_inicio >= :dini';
            $params['dini'] = $filters['inicio'];
        }
        if (!empty($filters['fim'])) {
            $where .= ' AND p.data_inicio <= :dfim';
            $params['dfim'] = $filters['fim'];
        }
        $count = $this->db->prepare("SELECT COUNT(*) FROM pessoas_pdis p JOIN colaboradores col ON col.id = p.colaborador_id WHERE $where");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $sql = "SELECT p.*, col.nome AS colaborador_nome, {$this->countsSql()}
                FROM pessoas_pdis p JOIN colaboradores col ON col.id = p.colaborador_id
                WHERE $where ORDER BY (p.status = 'ativo') DESC, p.created_at DESC, p.id DESC LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', max(1, $per), \PDO::PARAM_INT);
        $stmt->bindValue(':off', max(0, ($page - 1) * $per), \PDO::PARAM_INT);
        $stmt->execute();
        return ['items' => array_map(fn(array $r): array => $this->decorate($r), $stmt->fetchAll()), 'total' => $total];
    }

    // ---------------------------------------------------------------
    // Ciclo de vida
    // ---------------------------------------------------------------

    private function validDate($v): ?string
    {
        $v = trim((string)$v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    /** @return int id ou 0 */
    public function create(int $empresaId, int $colaboradorId, array $data, ColaboradorModel $colaboradores, int $userId): int
    {
        $colaborador = $colaboradores->find($colaboradorId);
        if (!$colaborador || (int)$colaborador['cliente_id'] !== $empresaId || !$this->canAccessClienteId($empresaId)) {
            return 0;
        }
        if (array_key_exists('ativo', $colaborador) && (int)$colaborador['ativo'] !== 1) {
            return 0;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            return 0;
        }
        $ini = $this->validDate($data['data_inicio'] ?? '');
        $fim = $this->validDate($data['data_fim_prevista'] ?? '');
        if ($ini !== null && $fim !== null && $fim < $ini) {
            return 0;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO pessoas_pdis (empresa_id, colaborador_id, titulo, descricao, data_inicio, data_fim_prevista, status, created_by)
             VALUES (:eid, :cid, :t, :d, :ini, :fim, 'rascunho', :uid)"
        );
        $stmt->execute([
            'eid' => $empresaId, 'cid' => $colaboradorId, 't' => mb_substr($titulo, 0, 255),
            'd' => trim((string)($data['descricao'] ?? '')) !== '' ? trim((string)$data['descricao']) : null,
            'ini' => $ini, 'fim' => $fim, 'uid' => $userId > 0 ? $userId : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Edição estrutural só em rascunho; colaborador/empresa nunca mudam. */
    public function update(int $id, array $data): bool
    {
        $pdi = $this->find($id);
        if (!$pdi || $pdi['status'] !== 'rascunho') {
            return false;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        $ini = $this->validDate($data['data_inicio'] ?? '');
        $fim = $this->validDate($data['data_fim_prevista'] ?? '');
        if ($titulo === '' || ($ini !== null && $fim !== null && $fim < $ini)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE pessoas_pdis SET titulo = :t, descricao = :d, data_inicio = :ini, data_fim_prevista = :fim WHERE id = :id AND status = 'rascunho'"
        );
        return $stmt->execute([
            't' => mb_substr($titulo, 0, 255),
            'd' => trim((string)($data['descricao'] ?? '')) !== '' ? trim((string)$data['descricao']) : null,
            'ini' => $ini, 'fim' => $fim, 'id' => $id,
        ]);
    }

    /** @return array{ok:bool,error:?string} */
    public function ativar(int $id): array
    {
        $pdi = $this->find($id);
        if (!$pdi) {
            return ['ok' => false, 'error' => 'PDI não encontrado.'];
        }
        if ($pdi['status'] !== 'rascunho') {
            return ['ok' => false, 'error' => 'Só é possível ativar um PDI em rascunho.'];
        }
        if (empty($pdi['data_inicio'])) {
            return ['ok' => false, 'error' => 'Informe a data de início para ativar o PDI.'];
        }
        if ((int)$pdi['objetivos_validos'] < 1) {
            return ['ok' => false, 'error' => 'Cadastre ao menos 1 objetivo (não cancelado) para ativar o PDI.'];
        }
        if ($this->pdiAtivoDoColaborador((int)$pdi['colaborador_id'], (int)$pdi['empresa_id']) !== null) {
            return ['ok' => false, 'error' => 'Este colaborador já possui um PDI ativo.'];
        }
        try {
            $stmt = $this->db->prepare("UPDATE pessoas_pdis SET status = 'ativo' WHERE id = :id AND status = 'rascunho'");
            $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            // Corrida: o UNIQUE do banco (coluna gerada) barra o 2º PDI ativo.
            return ['ok' => false, 'error' => 'Este colaborador já possui um PDI ativo.'];
        }
        return $stmt->rowCount() > 0 ? ['ok' => true, 'error' => null] : ['ok' => false, 'error' => 'Não foi possível ativar o PDI.'];
    }

    /** @return array{ok:bool,error:?string} */
    public function concluir(int $id, string $observacoes): array
    {
        $pdi = $this->find($id);
        if (!$pdi) {
            return ['ok' => false, 'error' => 'PDI não encontrado.'];
        }
        if ($pdi['status'] !== 'ativo') {
            return ['ok' => false, 'error' => 'Só é possível concluir um PDI ativo.'];
        }
        $observacoes = trim($observacoes);
        if ($observacoes === '') {
            return ['ok' => false, 'error' => 'Informe as observações de conclusão.'];
        }
        $validos = (int)$pdi['objetivos_validos'];
        if ($validos < 1 || (int)$pdi['objetivos_concluidos'] !== $validos) {
            return ['ok' => false, 'error' => 'Todos os objetivos (não cancelados) precisam estar concluídos.'];
        }
        $stmt = $this->db->prepare(
            "UPDATE pessoas_pdis SET status = 'concluido', observacoes_conclusao = :o, concluido_em = NOW() WHERE id = :id AND status = 'ativo'"
        );
        $stmt->execute(['o' => mb_substr($observacoes, 0, 2000), 'id' => $id]);
        return $stmt->rowCount() > 0 ? ['ok' => true, 'error' => null] : ['ok' => false, 'error' => 'Não foi possível concluir o PDI.'];
    }

    public function cancelar(int $id): bool
    {
        $pdi = $this->find($id);
        if (!$pdi || !in_array($pdi['status'], self::EDITAVEIS, true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE pessoas_pdis SET status = 'cancelado' WHERE id = :id AND status IN ('rascunho','ativo')");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ---------------------------------------------------------------
    // GAPs do PDI
    // ---------------------------------------------------------------

    public function gapsDoPdi(int $pdiId): array
    {
        $stmt = $this->db->prepare(
            'SELECT g.id, g.titulo, g.status, g.prioridade, g.origem FROM pessoas_pdi_gaps pg
             JOIN pessoas_gaps g ON g.id = pg.gap_id WHERE pg.pdi_id = :pid ORDER BY pg.id'
        );
        $stmt->execute(['pid' => $pdiId]);
        return $stmt->fetchAll();
    }

    /** PDI (rascunho/ativo) que já inclui o GAP - para "Incluído no PDI" na tela do GAP. */
    public function pdiDoGap(int $gapId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.titulo, p.status FROM pessoas_pdi_gaps pg JOIN pessoas_pdis p ON p.id = pg.pdi_id
             WHERE pg.gap_id = :gid AND p.status IN ('rascunho','ativo') ORDER BY (p.status = 'ativo') DESC LIMIT 1"
        );
        $stmt->execute(['gid' => $gapId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array{ok:bool,error:?string,already_existed:bool} */
    public function addGap(int $pdiId, int $gapId, PessoaGapModel $gaps): array
    {
        $pdi = $this->find($pdiId);
        if (!$pdi || !$this->canAccessClienteId((int)$pdi['empresa_id'])) {
            return ['ok' => false, 'error' => 'PDI não encontrado.', 'already_existed' => false];
        }
        if (!in_array($pdi['status'], self::EDITAVEIS, true)) {
            return ['ok' => false, 'error' => 'PDI não aceita novos GAPs neste status.', 'already_existed' => false];
        }
        // relação semântica: GAP do MESMO colaborador e MESMA empresa
        if ($gaps->findForColaborador($gapId, (int)$pdi['colaborador_id'], (int)$pdi['empresa_id']) === null) {
            return ['ok' => false, 'error' => 'GAP inválido para este colaborador.', 'already_existed' => false];
        }
        $dup = $this->db->prepare('SELECT 1 FROM pessoas_pdi_gaps WHERE pdi_id = :p AND gap_id = :g');
        $dup->execute(['p' => $pdiId, 'g' => $gapId]);
        if ($dup->fetchColumn()) {
            return ['ok' => true, 'error' => null, 'already_existed' => true];
        }
        try {
            $this->db->prepare('INSERT INTO pessoas_pdi_gaps (pdi_id, gap_id) VALUES (:p, :g)')->execute(['p' => $pdiId, 'g' => $gapId]);
        } catch (\PDOException $e) {
            return ['ok' => true, 'error' => null, 'already_existed' => true];
        }
        return ['ok' => true, 'error' => null, 'already_existed' => false];
    }

    public function removeGap(int $pdiId, int $gapId): bool
    {
        $pdi = $this->find($pdiId);
        if (!$pdi || $pdi['status'] !== 'rascunho') {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM pessoas_pdi_gaps WHERE pdi_id = :p AND gap_id = :g');
        $stmt->execute(['p' => $pdiId, 'g' => $gapId]);
        return $stmt->rowCount() > 0;
    }

    // ---------------------------------------------------------------
    // Objetivos
    // ---------------------------------------------------------------

    public function findObjetivo(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('p.empresa_id', $params, 'ppo');
        $stmt = $this->db->prepare(
            "SELECT o.*, p.empresa_id, p.colaborador_id, p.status AS pdi_status
             FROM pessoas_pdi_objetivos o JOIN pessoas_pdis p ON p.id = o.pdi_id
             WHERE o.id = :id AND $scope"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function objetivosDoPdi(int $pdiId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pessoas_pdi_objetivos WHERE pdi_id = :p ORDER BY ordem ASC, id ASC');
        $stmt->execute(['p' => $pdiId]);
        return $stmt->fetchAll();
    }

    /** @return int id ou 0 */
    public function addObjetivo(int $pdiId, array $data): int
    {
        $pdi = $this->find($pdiId);
        if (!$pdi || !in_array($pdi['status'], self::EDITAVEIS, true)) {
            return 0;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            return 0;
        }
        $ordem = (int)$this->db->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM pessoas_pdi_objetivos WHERE pdi_id = ' . (int)$pdiId)->fetchColumn();
        $stmt = $this->db->prepare(
            "INSERT INTO pessoas_pdi_objetivos (pdi_id, titulo, descricao, criterio_sucesso, prazo, ordem, status)
             VALUES (:p, :t, :d, :c, :prazo, :o, 'pendente')"
        );
        $stmt->execute([
            'p' => $pdiId, 't' => mb_substr($titulo, 0, 255),
            'd' => trim((string)($data['descricao'] ?? '')) !== '' ? trim((string)$data['descricao']) : null,
            'c' => trim((string)($data['criterio_sucesso'] ?? '')) !== '' ? mb_substr(trim((string)$data['criterio_sucesso']), 0, 1000) : null,
            'prazo' => $this->validDate($data['prazo'] ?? ''), 'o' => $ordem,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * pendente -> em_andamento | concluido | cancelado; em_andamento -> concluido | cancelado.
     * Concluir/iniciar exige PDI ativo; cancelar vale em rascunho/ativo. Não reabre.
     * Nunca altera Ações/GAPs/Planos/Treinamentos.
     */
    public function setObjetivoStatus(int $objetivoId, string $novo): bool
    {
        $o = $this->findObjetivo($objetivoId);
        if (!$o || !in_array($o['pdi_status'], self::EDITAVEIS, true)) {
            return false;
        }
        $permitido = match ($o['status']) {
            'pendente' => in_array($novo, ['em_andamento', 'concluido', 'cancelado'], true),
            'em_andamento' => in_array($novo, ['concluido', 'cancelado'], true),
            default => false,
        };
        if (!$permitido || ($novo !== 'cancelado' && $o['pdi_status'] !== 'ativo')) {
            return false;
        }
        $sql = $novo === 'concluido'
            ? 'UPDATE pessoas_pdi_objetivos SET status = :s, concluido_em = NOW() WHERE id = :id'
            : 'UPDATE pessoas_pdi_objetivos SET status = :s WHERE id = :id';
        return $this->db->prepare($sql)->execute(['s' => $novo, 'id' => $objetivoId]);
    }

    // ---------------------------------------------------------------
    // Ações do objetivo
    // ---------------------------------------------------------------

    public function acoesDoObjetivo(int $objetivoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ac.*, u.nome AS responsavel_nome FROM pessoas_pdi_objetivo_acoes oa
             JOIN pessoas_acoes_melhoria ac ON ac.id = oa.acao_melhoria_id
             LEFT JOIN usuarios u ON u.id = ac.responsavel_usuario_id
             WHERE oa.objetivo_id = :o ORDER BY oa.id'
        );
        $stmt->execute(['o' => $objetivoId]);
        return $stmt->fetchAll();
    }

    /** Ações elegíveis: do mesmo colaborador/empresa, ativas, ainda não vinculadas ao objetivo. */
    public function acoesDisponiveis(int $objetivoId, PessoaAcaoMelhoriaModel $acoes): array
    {
        $o = $this->findObjetivo($objetivoId);
        if (!$o) {
            return [];
        }
        $ja = array_map(static fn($a) => (int)$a['id'], $this->acoesDoObjetivo($objetivoId));
        return array_values(array_filter(
            $acoes->listByColaborador((int)$o['colaborador_id'], (int)$o['empresa_id']),
            static fn($a) => !in_array((int)$a['id'], $ja, true) && in_array($a['status'], ['pendente', 'em_andamento'], true)
        ));
    }

    /** @return array{ok:bool,error:?string,already_existed:bool} */
    public function vincularAcao(int $objetivoId, int $acaoId, PessoaAcaoMelhoriaModel $acoes): array
    {
        $o = $this->findObjetivo($objetivoId);
        if (!$o || !$this->canAccessClienteId((int)$o['empresa_id'])) {
            return ['ok' => false, 'error' => 'Objetivo não encontrado.', 'already_existed' => false];
        }
        if (!in_array($o['pdi_status'], self::EDITAVEIS, true)) {
            return ['ok' => false, 'error' => 'PDI não aceita novas ações neste status.', 'already_existed' => false];
        }
        $acao = $acoes->find($acaoId);
        if (!$acao || (int)$acao['empresa_id'] !== (int)$o['empresa_id'] || (int)$acao['colaborador_id'] !== (int)$o['colaborador_id']) {
            return ['ok' => false, 'error' => 'Ação inválida para este colaborador.', 'already_existed' => false];
        }
        $dup = $this->db->prepare('SELECT 1 FROM pessoas_pdi_objetivo_acoes WHERE objetivo_id = :o AND acao_melhoria_id = :a');
        $dup->execute(['o' => $objetivoId, 'a' => $acaoId]);
        if ($dup->fetchColumn()) {
            return ['ok' => true, 'error' => null, 'already_existed' => true];
        }
        try {
            $this->db->prepare('INSERT INTO pessoas_pdi_objetivo_acoes (objetivo_id, acao_melhoria_id) VALUES (:o, :a)')->execute(['o' => $objetivoId, 'a' => $acaoId]);
        } catch (\PDOException $e) {
            return ['ok' => true, 'error' => null, 'already_existed' => true];
        }
        return ['ok' => true, 'error' => null, 'already_existed' => false];
    }

    /**
     * Cria a Ação pelo fluxo existente (PessoaAcaoMelhoriaModel::create) e a
     * vincula ao objetivo, atomicamente. gap_id só é aceito se o GAP fizer
     * parte do PDI (e o create() valida colaborador/empresa).
     *
     * @return array{ok:bool,error:?string,acao_id:int}
     */
    public function criarAcaoNoObjetivo(int $objetivoId, array $data, PessoaAcaoMelhoriaModel $acoes, PessoaGapModel $gaps, PessoaAvaliacaoModel $avaliacoes, ColaboradorModel $colaboradores, int $userId): array
    {
        $o = $this->findObjetivo($objetivoId);
        if (!$o || !$this->canAccessClienteId((int)$o['empresa_id'])) {
            return ['ok' => false, 'error' => 'Objetivo não encontrado.', 'acao_id' => 0];
        }
        if (!in_array($o['pdi_status'], self::EDITAVEIS, true)) {
            return ['ok' => false, 'error' => 'PDI não aceita novas ações neste status.', 'acao_id' => 0];
        }
        $gapId = (int)($data['gap_id'] ?? 0);
        if ($gapId > 0) {
            $noPdi = array_column($this->gapsDoPdi((int)$o['pdi_id']), 'id');
            if (!in_array($gapId, array_map('intval', $noPdi), true)) {
                $gapId = 0;
            }
        }
        $owns = !$this->db->inTransaction();
        if ($owns) {
            $this->db->beginTransaction();
        }
        try {
            $acaoId = $acoes->create((int)$o['empresa_id'], (int)$o['colaborador_id'], [
                'titulo' => $data['titulo'] ?? '',
                'descricao' => $data['descricao'] ?? null,
                'prazo' => $data['prazo'] ?? ($o['prazo'] ?? null),
                'responsavel_usuario_id' => $data['responsavel_usuario_id'] ?? 0,
                'gap_id' => $gapId,
            ], $colaboradores, $gaps, $avaliacoes, $userId);
            if ($acaoId <= 0) {
                if ($owns) { $this->db->rollBack(); }
                return ['ok' => false, 'error' => 'Não foi possível criar a Ação. Verifique o título.', 'acao_id' => 0];
            }
            $this->db->prepare('INSERT INTO pessoas_pdi_objetivo_acoes (objetivo_id, acao_melhoria_id) VALUES (:o, :a)')->execute(['o' => $objetivoId, 'a' => $acaoId]);
            if ($owns) { $this->db->commit(); }
            return ['ok' => true, 'error' => null, 'acao_id' => $acaoId];
        } catch (\Throwable $e) {
            if ($owns && $this->db->inTransaction()) { $this->db->rollBack(); }
            return ['ok' => false, 'error' => 'Não foi possível criar a Ação.', 'acao_id' => 0];
        }
    }

    /** Feedbacks do colaborador registrados durante o período do PDI (sem FK artificial). */
    public function feedbacksNoPeriodo(array $pdi, int $limit = 10): array
    {
        if (empty($pdi['data_inicio'])) {
            return [];
        }
        $fim = !empty($pdi['concluido_em']) ? substr((string)$pdi['concluido_em'], 0, 10) : (!empty($pdi['data_fim_prevista']) ? max((string)$pdi['data_fim_prevista'], date('Y-m-d')) : date('Y-m-d'));
        $stmt = $this->db->prepare(
            'SELECT id, tipo, titulo, data_feedback FROM pessoas_feedbacks
             WHERE colaborador_id = :c AND empresa_id = :e AND data_feedback BETWEEN :i AND :f ORDER BY data_feedback DESC, id DESC LIMIT :l'
        );
        $stmt->bindValue(':c', (int)$pdi['colaborador_id'], \PDO::PARAM_INT);
        $stmt->bindValue(':e', (int)$pdi['empresa_id'], \PDO::PARAM_INT);
        $stmt->bindValue(':i', $pdi['data_inicio']);
        $stmt->bindValue(':f', $fim);
        $stmt->bindValue(':l', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

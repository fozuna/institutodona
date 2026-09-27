<?php
namespace App\Models;

use App\Core\PessoasGestaoConfig;

/**
 * Pilar de Pessoas, Sprint 05 - leitura operacional: listagens de GAPs,
 * Ações de Melhoria e Necessidades de Treinamento + consolidação da Central
 * do Colaborador. Somente consulta (nenhuma mutação, nenhum audit log).
 *
 * Regras:
 *  - Escopo: empresas já validadas pelo Controller + tenantInCondition() no
 *    SQL (trait PessoasEscopoSql, a MESMA usada pela Visão Geral).
 *  - Predicados de negócio vêm de PessoasGestaoConfig (ação vencida, GAP sem
 *    ação): contadores e listagens usam exatamente a mesma regra.
 *  - Paginação no SQL; colunas agregadas por subconsulta correlacionada
 *    (avaliadas só para a página) - número constante de consultas, sem N+1.
 *  - Filtros chegam já normalizados/whitelisted do Controller; aqui ainda
 *    há defesa (in_array) para nunca interpolar valor livre.
 */
class PessoaOperacionalModel extends BaseModel
{
    use PessoasEscopoSql;

    public const GAP_STATUS = ['aberto', 'em_tratamento', 'resolvido', 'nao_resolvido'];
    public const ACAO_STATUS = ['pendente', 'em_andamento', 'concluida', 'cancelada', 'ativas'];
    public const NECESSIDADE_STATUS = ['pendente', 'atendida', 'cancelada'];

    /** Filtros comuns (colaborador, busca por nome, período de registro) sobre alias da tabela principal. */
    private function filtrosComuns(string $alias, array $f, array &$p): string
    {
        $sql = '';
        if (!empty($f['colaborador_id'])) {
            $sql .= " AND {$alias}.colaborador_id = :colid";
            $p['colid'] = (int)$f['colaborador_id'];
        }
        if (isset($f['q']) && trim((string)$f['q']) !== '') {
            $sql .= ' AND col.nome LIKE :q';
            $p['q'] = '%' . trim((string)$f['q']) . '%';
        }
        if (!empty($f['inicio'])) {
            $sql .= " AND {$alias}.created_at >= :dini";
            $p['dini'] = $f['inicio'] . ' 00:00:00';
        }
        if (!empty($f['fim'])) {
            $sql .= " AND {$alias}.created_at < :dfim";
            $p['dfim'] = date('Y-m-d', strtotime($f['fim'] . ' +1 day')) . ' 00:00:00';
        }
        return $sql;
    }

    /** @return array{items:array,total:int} */
    private function paginar(string $from, string $where, string $select, string $order, array $p, int $page, int $per): array
    {
        $total = (int)($this->row("SELECT COUNT(*) AS n FROM $from WHERE $where", $p)['n'] ?? 0);
        $per = max(1, $per);
        $page = max(1, min($page, max(1, (int)ceil($total / $per))));
        $p['lim'] = $per;
        $p['off'] = ($page - 1) * $per;
        $items = $total > 0 ? $this->rows("SELECT $select FROM $from WHERE $where ORDER BY $order LIMIT :lim OFFSET :off", $p) : [];
        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    // ---------------------------------------------------------------
    // GAPs
    // ---------------------------------------------------------------

    /**
     * @param int[] $empresaIds
     * @param array{status?:string,tratamento?:string,colaborador_id?:int,q?:string,inicio?:string,fim?:string,departamento_id?:int,setor_id?:int,funcao_id?:int} $f
     * @return array{items:array,total:int,page:int}
     */
    public function listarGaps(array $empresaIds, array $f, int $page, int $per): array
    {
        $p = [];
        $where = $this->inClause('g.empresa_id', $empresaIds, $p, 'ge') . $this->orgFilter($f, $p, 'go') . $this->filtrosComuns('g', $f, $p);
        $status = (string)($f['status'] ?? '');
        if (in_array($status, ['aberto', 'em_tratamento', 'resolvido'], true)) {
            $where .= ' AND g.status = :gst';
            $p['gst'] = $status;
        } elseif ($status === 'nao_resolvido') {
            $where .= " AND g.status IN ('aberto','em_tratamento')";
        }
        $tratamento = (string)($f['tratamento'] ?? '');
        if ($tratamento === 'com_acao') {
            $where .= ' AND ' . PessoasGestaoConfig::gapComAcaoSql('g');
        } elseif ($tratamento === 'sem_acao') {
            $where .= ' AND NOT ' . PessoasGestaoConfig::gapComAcaoSql('g');
        }
        $from = 'pessoas_gaps g JOIN colaboradores col ON col.id = g.colaborador_id JOIN clientes c ON c.id = g.empresa_id
                 LEFT JOIN pessoas_avaliacoes a ON a.id = g.avaliacao_id
                 LEFT JOIN pessoas_ciclos_avaliacao cic ON cic.id = a.ciclo_id';
        $select = "g.id, g.empresa_id, g.colaborador_id, g.avaliacao_id, g.titulo, g.descricao, g.origem, g.prioridade, g.status,
                   g.created_at, g.resolvido_em, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome, cic.nome AS ciclo_nome,
                   a.status AS avaliacao_status,
                   (SELECT COUNT(*) FROM pessoas_acoes_melhoria am WHERE am.gap_id = g.id AND am.status <> 'cancelada') AS acoes_total,
                   (SELECT COUNT(*) FROM pessoas_acoes_melhoria am WHERE am.gap_id = g.id AND am.status IN ('pendente','em_andamento')) AS acoes_ativas,
                   (SELECT MIN(am.id) FROM pessoas_acoes_melhoria am WHERE am.gap_id = g.id AND am.status <> 'cancelada') AS primeira_acao_id";
        $order = "FIELD(g.status, 'aberto', 'em_tratamento', 'resolvido'), FIELD(g.prioridade, 'alta', 'media', 'baixa'), g.created_at DESC, g.id DESC";
        return $this->paginar($from, $where, $select, $order, $p, $page, $per);
    }

    // ---------------------------------------------------------------
    // Ações de Melhoria
    // ---------------------------------------------------------------

    /**
     * @param int[] $empresaIds
     * @return array{items:array,total:int,page:int}
     */
    public function listarAcoes(array $empresaIds, array $f, int $page, int $per): array
    {
        $p = [];
        $where = $this->inClause('ac.empresa_id', $empresaIds, $p, 'ae') . $this->orgFilter($f, $p, 'ao') . $this->filtrosComuns('ac', $f, $p);
        $status = (string)($f['status'] ?? '');
        if (in_array($status, ['pendente', 'em_andamento', 'concluida', 'cancelada'], true)) {
            $where .= ' AND ac.status = :ast';
            $p['ast'] = $status;
        } elseif ($status === 'ativas') {
            $where .= " AND ac.status IN ('pendente','em_andamento')";
        }
        if (!empty($f['atrasadas'])) {
            $where .= ' AND ' . PessoasGestaoConfig::acaoVencidaSql('ac');
        }
        if (!empty($f['responsavel_id'])) {
            $where .= ' AND ac.responsavel_usuario_id = :resp';
            $p['resp'] = (int)$f['responsavel_id'];
        }
        if (!empty($f['com_plano'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM pessoas_acao_planos apx WHERE apx.acao_melhoria_id = ac.id)';
        }
        if (!empty($f['com_treinamento'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM pessoas_acao_treinamentos atx WHERE atx.acao_melhoria_id = ac.id)';
        }
        $from = 'pessoas_acoes_melhoria ac JOIN colaboradores col ON col.id = ac.colaborador_id JOIN clientes c ON c.id = ac.empresa_id
                 LEFT JOIN usuarios u ON u.id = ac.responsavel_usuario_id
                 LEFT JOIN pessoas_gaps g ON g.id = ac.gap_id
                 LEFT JOIN pessoas_acao_planos ap ON ap.acao_melhoria_id = ac.id
                 LEFT JOIN pdca_tasks pt ON pt.id = ap.plano_task_id';
        $vencida = PessoasGestaoConfig::acaoVencidaSql('ac');
        $select = "ac.id, ac.empresa_id, ac.colaborador_id, ac.gap_id, ac.titulo, ac.descricao, ac.status, ac.prazo, ac.data_inicio,
                   ac.created_at, ac.concluido_em, ac.responsavel_usuario_id, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome,
                   u.nome AS responsavel_nome, g.titulo AS gap_titulo,
                   CASE WHEN $vencida THEN 1 ELSE 0 END AS vencida,
                   ap.plano_task_id, pt.titulo AS plano_titulo, pt.status AS plano_status,
                   (SELECT COUNT(*) FROM pessoas_acao_treinamentos at2 WHERE at2.acao_melhoria_id = ac.id) AS treinamentos_total,
                   (SELECT COUNT(*) FROM pessoas_necessidades_treinamento n2 WHERE n2.acao_melhoria_id = ac.id AND n2.status = 'pendente') AS necessidades_pendentes";
        $order = "CASE WHEN $vencida THEN 0 ELSE 1 END, FIELD(ac.status, 'pendente', 'em_andamento', 'concluida', 'cancelada'),
                  ac.prazo IS NULL, ac.prazo, ac.id DESC";
        return $this->paginar($from, $where, $select, $order, $p, $page, $per);
    }

    // ---------------------------------------------------------------
    // Necessidades de Treinamento
    // ---------------------------------------------------------------

    /**
     * @param int[] $empresaIds
     * @return array{items:array,total:int,page:int}
     */
    public function listarNecessidades(array $empresaIds, array $f, int $page, int $per): array
    {
        $p = [];
        $where = $this->inClause('n.empresa_id', $empresaIds, $p, 'ne') . $this->orgFilter($f, $p, 'no') . $this->filtrosComuns('n', $f, $p);
        $status = (string)($f['status'] ?? '');
        if (in_array($status, self::NECESSIDADE_STATUS, true)) {
            $where .= ' AND n.status = :nst';
            $p['nst'] = $status;
        }
        $from = 'pessoas_necessidades_treinamento n JOIN colaboradores col ON col.id = n.colaborador_id JOIN clientes c ON c.id = n.empresa_id
                 LEFT JOIN pessoas_acoes_melhoria ac ON ac.id = n.acao_melhoria_id
                 LEFT JOIN treinamentos t ON t.id = n.treinamento_id';
        $select = 'n.id, n.empresa_id, n.colaborador_id, n.acao_melhoria_id, n.titulo, n.descricao, n.prioridade, n.status,
                   n.treinamento_id, n.created_at, n.atendida_em, col.nome AS colaborador_nome, c.nome_empresa AS empresa_nome,
                   ac.titulo AS acao_titulo, t.nome AS treinamento_nome';
        $order = "FIELD(n.status, 'pendente', 'atendida', 'cancelada'), FIELD(n.prioridade, 'alta', 'media', 'baixa'), n.created_at DESC, n.id DESC";
        return $this->paginar($from, $where, $select, $order, $p, $page, $per);
    }

    // ---------------------------------------------------------------
    // Central do Colaborador
    // ---------------------------------------------------------------

    /** Posição organizacional + empresa (tenant reforçado no SQL). */
    public function perfilOrganizacional(int $colaboradorId): array
    {
        $p = ['id' => $colaboradorId];
        $scope = $this->tenantInCondition('col.cliente_id', $p, 'pop');
        return $this->row(
            "SELECT c.nome_empresa AS empresa_nome, f.nome AS funcao_nome, s.nome AS setor_nome, d.nome AS departamento_nome
             FROM colaboradores col
             JOIN clientes c ON c.id = col.cliente_id
             LEFT JOIN funcoes f ON f.id = col.funcao_id
             LEFT JOIN setores s ON s.id = f.setor_id
             LEFT JOIN departamentos d ON d.id = s.departamento_id
             WHERE col.id = :id AND $scope",
            $p
        );
    }

    /**
     * Contadores do colaborador numa única consulta, com os MESMOS predicados
     * das listagens (status, ação vencida, GAP sem ação).
     */
    public function resumoColaborador(int $colaboradorId, int $empresaId): array
    {
        $p = ['cid' => $colaboradorId, 'eid' => $empresaId];
        $vencida = PessoasGestaoConfig::acaoVencidaSql('ac');
        $semAcao = PessoasGestaoConfig::gapAbertoSemAcaoSql('g');
        $r = $this->row(
            "SELECT
                (SELECT COUNT(*) FROM pessoas_gaps g WHERE g.colaborador_id = :cid AND g.empresa_id = :eid AND g.status = 'aberto') AS gaps_abertos,
                (SELECT COUNT(*) FROM pessoas_gaps g WHERE g.colaborador_id = :cid AND g.empresa_id = :eid AND g.status = 'em_tratamento') AS gaps_em_tratamento,
                (SELECT COUNT(*) FROM pessoas_gaps g WHERE g.colaborador_id = :cid AND g.empresa_id = :eid AND $semAcao) AS gaps_abertos_sem_acao,
                (SELECT COUNT(*) FROM pessoas_acoes_melhoria ac WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid AND ac.status = 'pendente') AS acoes_pendentes,
                (SELECT COUNT(*) FROM pessoas_acoes_melhoria ac WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid AND ac.status = 'em_andamento') AS acoes_em_andamento,
                (SELECT COUNT(*) FROM pessoas_acoes_melhoria ac WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid AND $vencida) AS acoes_vencidas,
                (SELECT COUNT(*) FROM pessoas_necessidades_treinamento n WHERE n.colaborador_id = :cid AND n.empresa_id = :eid AND n.status = 'pendente') AS necessidades_pendentes,
                (SELECT COUNT(*) FROM pessoas_avaliacoes a WHERE a.colaborador_id = :cid AND a.empresa_id = :eid AND a.status = 'pendente') AS avaliacoes_pendentes,
                (SELECT COUNT(*) FROM pessoas_avaliacoes a WHERE a.colaborador_id = :cid AND a.empresa_id = :eid AND a.status = 'em_andamento') AS avaliacoes_em_andamento",
            $p
        );
        return array_map('intval', $r);
    }

    /** Treinamentos em que o colaborador está na lista (fonte: módulo de Treinamentos, sem duplicar status). */
    public function treinamentosDoColaborador(int $colaboradorId, int $empresaId): array
    {
        $p = ['cid' => $colaboradorId, 'eid' => $empresaId];
        $scope = $this->tenantInCondition('COALESCE(t.cliente_id, d.cliente_id)', $p, 'ptc');
        return $this->rows(
            "SELECT t.id, t.nome, tc.status, tc.status_detalhe, t.encerrado_em
             FROM treinamento_colaboradores tc
             JOIN treinamentos t ON t.id = tc.treinamento_id
             JOIN departamentos d ON d.id = t.departamento_id
             WHERE tc.colaborador_id = :cid AND COALESCE(t.cliente_id, d.cliente_id) = :eid AND $scope
             ORDER BY (tc.status = 'concluido'), t.nome",
            $p
        );
    }

    /** Datas dos encaminhamentos das Ações (timeline) - 1 consulta por tipo, por colaborador. */
    public function encaminhamentosDoColaborador(int $colaboradorId, int $empresaId): array
    {
        $p = ['cid' => $colaboradorId, 'eid' => $empresaId];
        $planos = $this->rows(
            'SELECT ap.acao_melhoria_id, ap.created_at, ac.titulo AS acao_titulo FROM pessoas_acao_planos ap
             JOIN pessoas_acoes_melhoria ac ON ac.id = ap.acao_melhoria_id
             WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid',
            $p
        );
        $treinamentos = $this->rows(
            'SELECT at.acao_melhoria_id, at.created_at, t.nome AS treinamento_nome FROM pessoas_acao_treinamentos at
             JOIN pessoas_acoes_melhoria ac ON ac.id = at.acao_melhoria_id JOIN treinamentos t ON t.id = at.treinamento_id
             WHERE ac.colaborador_id = :cid AND ac.empresa_id = :eid',
            $p
        );
        $necessidades = $this->rows(
            'SELECT n.id, n.titulo, n.status, n.created_at, n.atendida_em, n.acao_melhoria_id FROM pessoas_necessidades_treinamento n
             WHERE n.colaborador_id = :cid AND n.empresa_id = :eid ORDER BY n.created_at DESC, n.id DESC',
            $p
        );
        return ['planos' => $planos, 'treinamentos' => $treinamentos, 'necessidades' => $necessidades];
    }

    /**
     * Timeline derivada dos registros já carregados (nenhuma tabela nova,
     * nenhum evento copiado). Ordem decrescente por data.
     *
     * @return array<int,array{data:string,texto:string,tipo:string,href:string}>
     */
    public static function timeline(array $avaliacoes, array $gaps, array $feedbacks, array $acoes, array $encaminhamentos, array $pdis, int $limite = 30): array
    {
        $ev = [];
        $add = static function (?string $data, string $tipo, string $texto, string $href = '') use (&$ev): void {
            if ($data !== null && $data !== '' && strtotime($data) !== false) {
                $ev[] = ['data' => $data, 'tipo' => $tipo, 'texto' => $texto, 'href' => $href];
            }
        };
        foreach ($avaliacoes as $a) {
            if (($a['status'] ?? '') === 'finalizada') {
                $add($a['finalizado_em'] ?? null, 'avaliacao', 'Avaliação concluída — ' . ($a['ciclo_nome'] ?? ''), 'index.php?route=pessoas/avaliacaoResultado&id=' . (int)$a['id']);
            }
        }
        foreach ($gaps as $g) {
            $href = 'index.php?route=pessoas/gapShow&id=' . (int)$g['id'];
            $add($g['created_at'] ?? null, 'gap', 'GAP registrado — ' . $g['titulo'], $href);
            $add($g['resolvido_em'] ?? null, 'gap', 'GAP resolvido — ' . $g['titulo'], $href);
        }
        $fbLabels = PessoasGestaoConfig::feedbackTipoLabels();
        foreach ($feedbacks as $f) {
            $add($f['data_feedback'] ?? null, 'feedback', ($fbLabels[$f['tipo']] ?? 'Feedback') . ' registrado — ' . $f['titulo']);
        }
        foreach ($acoes as $a) {
            $href = 'index.php?route=pessoas/acaoShow&id=' . (int)$a['id'];
            $add($a['created_at'] ?? null, 'acao', 'Ação de melhoria criada — ' . $a['titulo'], $href);
            if (($a['status'] ?? '') === 'concluida') {
                $add($a['concluido_em'] ?? null, 'acao', 'Ação concluída — ' . $a['titulo'], $href);
            }
        }
        foreach ($encaminhamentos['planos'] ?? [] as $e) {
            $add($e['created_at'] ?? null, 'encaminhamento', 'Ação encaminhada para Plano de Ação — ' . $e['acao_titulo'], 'index.php?route=pessoas/acaoShow&id=' . (int)$e['acao_melhoria_id']);
        }
        foreach ($encaminhamentos['treinamentos'] ?? [] as $e) {
            $add($e['created_at'] ?? null, 'encaminhamento', 'Ação encaminhada para treinamento — ' . $e['treinamento_nome'], 'index.php?route=pessoas/acaoShow&id=' . (int)$e['acao_melhoria_id']);
        }
        foreach ($encaminhamentos['necessidades'] ?? [] as $n) {
            $href = 'index.php?route=pessoas/acaoShow&id=' . (int)$n['acao_melhoria_id'];
            $add($n['created_at'] ?? null, 'necessidade', 'Necessidade de treinamento registrada — ' . $n['titulo'], $href);
            if (($n['status'] ?? '') === 'atendida') {
                $add($n['atendida_em'] ?? null, 'necessidade', 'Necessidade de treinamento atendida — ' . $n['titulo'], $href);
            }
        }
        foreach ($pdis as $p) {
            $href = 'index.php?route=pessoas/pdiShow&id=' . (int)$p['id'];
            $add($p['created_at'] ?? null, 'pdi', 'PDI criado — ' . $p['titulo'], $href);
            if (($p['status'] ?? '') === 'concluido') {
                $add($p['concluido_em'] ?? null, 'pdi', 'PDI concluído — ' . $p['titulo'], $href);
            }
        }
        usort($ev, static fn(array $x, array $y): int => strcmp($y['data'], $x['data']));
        return array_slice($ev, 0, max(1, $limite));
    }

    /**
     * Pontos de atenção do colaborador: somente fatos objetivos e acionáveis
     * (sem score, sem classificação da pessoa).
     *
     * @return array<int,array{texto:string,href:string}>
     */
    public static function pontosDeAtencao(int $colaboradorId, array $resumo, ?array $pdiAtivo): array
    {
        $colQs = '&colaborador_id=' . $colaboradorId;
        $pts = [];
        if ($resumo['gaps_abertos_sem_acao'] > 0) {
            $pts[] = ['texto' => $resumo['gaps_abertos_sem_acao'] . ' GAP(s) aberto(s) sem ação de melhoria', 'href' => 'index.php?route=pessoas/gaps&status=aberto&tratamento=sem_acao' . $colQs];
        }
        if ($resumo['acoes_vencidas'] > 0) {
            $pts[] = ['texto' => $resumo['acoes_vencidas'] . ' ação(ões) de melhoria atrasada(s)', 'href' => 'index.php?route=pessoas/acoes&atrasadas=1' . $colQs];
        }
        if ($resumo['necessidades_pendentes'] > 0) {
            $pts[] = ['texto' => $resumo['necessidades_pendentes'] . ' necessidade(s) de treinamento pendente(s)', 'href' => 'index.php?route=pessoas/necessidades&status=pendente' . $colQs];
        }
        $avPend = $resumo['avaliacoes_pendentes'] + $resumo['avaliacoes_em_andamento'];
        if ($avPend > 0) {
            $pts[] = ['texto' => $avPend . ' avaliação(ões) pendente(s) ou em andamento', 'href' => '#avaliacoes'];
        }
        if ($pdiAtivo !== null) {
            $pendentes = max(0, (int)$pdiAtivo['objetivos_validos'] - (int)$pdiAtivo['objetivos_concluidos']);
            if ($pendentes > 0) {
                $pts[] = ['texto' => 'PDI ativo com ' . $pendentes . ' objetivo(s) pendente(s)', 'href' => 'index.php?route=pessoas/pdiShow&id=' . (int)$pdiAtivo['id']];
            }
        }
        return $pts;
    }
}

<?php
namespace App\Models;

use App\Core\PessoasAvaliacaoScale;
use App\Core\PessoasGestaoConfig;

/**
 * Pilar de Pessoas, Sprint 04 - Visão Geral (gerencial). Somente leitura,
 * agregações em SQL (número constante de consultas - sem N+1 e sem agregação
 * pesada em PHP).
 *
 * Duas naturezas de métrica (documentadas na tela):
 *  - ESTADO ATUAL (não filtrado por período): colaboradores ativos, avaliações
 *    pendentes/em andamento, GAPs abertos/em tratamento, ações
 *    pendentes/em andamento/vencidas, PDIs rascunho/ativos, necessidades
 *    pendentes, objetivos vencidos.
 *  - EVENTO NO PERÍODO: finalizado_em (avaliações finalizadas, resultado médio,
 *    distribuição), resolvido_em (GAPs resolvidos), concluido_em (ações e PDIs
 *    concluídos), created_at do vínculo (ações encaminhadas a Plano/Treinamento).
 *
 * Filtros de Departamento/Setor/Função atuam sobre o colaborador.
 */
class PessoaDashboardModel extends BaseModel
{
    /** @param int[] $ids */
    private function inClause(string $column, array $ids, array &$params, string $prefix): string
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0));
        if (empty($ids)) {
            return '1 = 0';
        }
        $ph = [];
        foreach ($ids as $i => $id) {
            $params[$prefix . $i] = $id;
            $ph[] = ':' . $prefix . $i;
        }
        return $column . ' IN (' . implode(',', $ph) . ') AND ' . $this->tenantInCondition($column, $params, $prefix . 't');
    }

    /** Filtros organizacionais aplicados sobre o alias `col` (colaboradores). */
    private function orgFilter(array $filters, array &$params, string $prefix): string
    {
        $sql = '';
        if (!empty($filters['funcao_id'])) {
            $sql .= " AND col.funcao_id = :{$prefix}f";
            $params[$prefix . 'f'] = (int)$filters['funcao_id'];
        } elseif (!empty($filters['setor_id'])) {
            $sql .= " AND col.funcao_id IN (SELECT fu.id FROM funcoes fu WHERE fu.setor_id = :{$prefix}s)";
            $params[$prefix . 's'] = (int)$filters['setor_id'];
        } elseif (!empty($filters['departamento_id'])) {
            $sql .= " AND col.funcao_id IN (SELECT fu.id FROM funcoes fu JOIN setores se ON se.id = fu.setor_id WHERE se.departamento_id = :{$prefix}d)";
            $params[$prefix . 'd'] = (int)$filters['departamento_id'];
        }
        return $sql;
    }

    /**
     * PDO (sem emulação) não aceita o mesmo placeholder nomeado mais de uma vez:
     * cada repetição vira `:nome__N` com o mesmo valor.
     */
    private function row(string $sql, array $params): array
    {
        $seen = [];
        $sql = preg_replace_callback('/:([A-Za-z_][A-Za-z0-9_]*)/', static function (array $m) use (&$seen, &$params): string {
            $name = $m[1];
            if (!array_key_exists($name, $params) && !isset($seen[$name])) {
                return $m[0];
            }
            $seen[$name] = ($seen[$name] ?? 0) + 1;
            if ($seen[$name] === 1) {
                return $m[0];
            }
            $alias = $name . '__' . $seen[$name];
            $params[$alias] = $params[$name];
            return ':' . $alias;
        }, $sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    /** Faixas de classificação -> SUM(CASE) derivados de PessoasAvaliacaoScale (sem duplicar limites). */
    private function bandSumsSql(string $inPeriodCond): string
    {
        $bands = PessoasAvaliacaoScale::classificationBands();
        $parts = [];
        foreach ($bands as $i => $b) {
            $cond = $i === 0 ? '1 = 1' : sprintf('a.resultado >= %F', $b['min']);
            if (isset($bands[$i + 1])) {
                $cond .= sprintf(' AND a.resultado < %F', $bands[$i + 1]['min']);
            }
            $parts[] = "COALESCE(SUM(CASE WHEN $inPeriodCond AND a.resultado IS NOT NULL AND $cond THEN 1 ELSE 0 END), 0) AS faixa_$i";
        }
        return implode(",\n            ", $parts);
    }

    /**
     * @param int[] $empresaIds empresas acessíveis já resolvidas pelo Controller
     * @param array{inicio:string,fim:string,departamento_id?:int,setor_id?:int,funcao_id?:int} $filters
     */
    public function resumo(array $empresaIds, array $filters): array
    {
        $ini = $filters['inicio'] . ' 00:00:00';
        $fimExclusivo = date('Y-m-d', strtotime($filters['fim'] . ' +1 day')) . ' 00:00:00';
        $per = static fn(string $col): string => "($col >= :ini AND $col < :fim)";
        $periodParams = ['ini' => $ini, 'fim' => $fimExclusivo];

        // 1) Colaboradores ativos (estado atual)
        $p = [];
        $sql = 'SELECT COUNT(*) AS n FROM colaboradores col WHERE ' . $this->inClause('col.cliente_id', $empresaIds, $p, 'e') . ' AND col.ativo = 1' . $this->orgFilter($filters, $p, 'o');
        $colaboradoresAtivos = (int)($this->row($sql, $p)['n'] ?? 0);

        // 2) Avaliações
        $p = $periodParams;
        $inFin = "a.status = 'finalizada' AND " . $per('a.finalizado_em');
        $sql = "SELECT
            COALESCE(SUM(a.status = 'pendente'), 0) AS pendentes,
            COALESCE(SUM(a.status = 'em_andamento'), 0) AS em_andamento,
            COALESCE(SUM(CASE WHEN $inFin THEN 1 ELSE 0 END), 0) AS finalizadas,
            AVG(CASE WHEN $inFin AND a.resultado IS NOT NULL THEN a.resultado END) AS media,
            {$this->bandSumsSql($inFin)}
            FROM pessoas_avaliacoes a JOIN colaboradores col ON col.id = a.colaborador_id
            WHERE " . $this->inClause('a.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $av = $this->row($sql, $p);
        $distribuicao = [];
        foreach (PessoasAvaliacaoScale::classificationBands() as $i => $b) {
            $distribuicao[] = ['label' => $b['label'], 'total' => (int)($av['faixa_' . $i] ?? 0)];
        }

        // 3) GAPs
        $p = $periodParams;
        $sql = "SELECT
            COALESCE(SUM(g.status = 'aberto'), 0) AS abertos,
            COALESCE(SUM(g.status = 'em_tratamento'), 0) AS em_tratamento,
            COALESCE(SUM(CASE WHEN g.status = 'resolvido' AND " . $per('g.resolvido_em') . " THEN 1 ELSE 0 END), 0) AS resolvidos
            FROM pessoas_gaps g JOIN colaboradores col ON col.id = g.colaborador_id
            WHERE " . $this->inClause('g.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $gaps = $this->row($sql, $p);

        // 4) Ações de melhoria
        $p = $periodParams;
        $sql = "SELECT
            COALESCE(SUM(ac.status = 'pendente'), 0) AS pendentes,
            COALESCE(SUM(ac.status = 'em_andamento'), 0) AS em_andamento,
            COALESCE(SUM(CASE WHEN ac.status = 'concluida' AND " . $per('ac.concluido_em') . " THEN 1 ELSE 0 END), 0) AS concluidas,
            COALESCE(SUM(CASE WHEN " . PessoasGestaoConfig::acaoVencidaSql('ac') . " THEN 1 ELSE 0 END), 0) AS vencidas
            FROM pessoas_acoes_melhoria ac JOIN colaboradores col ON col.id = ac.colaborador_id
            WHERE " . $this->inClause('ac.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $acoes = $this->row($sql, $p);

        // 5) Ações encaminhadas ao Plano de Ação / Treinamento (evento no período: criação do vínculo)
        $encaminhadas = [];
        foreach (['planos' => 'pessoas_acao_planos', 'treinamentos' => 'pessoas_acao_treinamentos'] as $key => $table) {
            $p = $periodParams;
            $sql = "SELECT COUNT(DISTINCT v.acao_melhoria_id) AS n FROM $table v
                JOIN pessoas_acoes_melhoria ac ON ac.id = v.acao_melhoria_id
                JOIN colaboradores col ON col.id = ac.colaborador_id
                WHERE " . $per('v.created_at') . ' AND ' . $this->inClause('v.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
            $encaminhadas[$key] = (int)($this->row($sql, $p)['n'] ?? 0);
        }

        // 6) Necessidades de treinamento pendentes (estado atual)
        $p = [];
        $sql = "SELECT COUNT(*) AS n FROM pessoas_necessidades_treinamento nt JOIN colaboradores col ON col.id = nt.colaborador_id
            WHERE nt.status = 'pendente' AND " . $this->inClause('nt.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $necessidadesPendentes = (int)($this->row($sql, $p)['n'] ?? 0);

        // 7) PDIs
        $p = $periodParams;
        $sql = "SELECT
            COALESCE(SUM(d.status = 'rascunho'), 0) AS rascunho,
            COALESCE(SUM(d.status = 'ativo'), 0) AS ativos,
            COALESCE(SUM(CASE WHEN d.status = 'concluido' AND " . $per('d.concluido_em') . " THEN 1 ELSE 0 END), 0) AS concluidos,
            COALESCE(SUM(d.status = 'ativo' AND d.data_fim_prevista IS NOT NULL AND d.data_fim_prevista < CURDATE()), 0) AS ativos_atrasados
            FROM pessoas_pdis d JOIN colaboradores col ON col.id = d.colaborador_id
            WHERE " . $this->inClause('d.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $pdis = $this->row($sql, $p);

        // 8) Objetivos vencidos (PDIs ativos; estado atual)
        $p = [];
        $sql = "SELECT COUNT(*) AS n FROM pessoas_pdi_objetivos o
            JOIN pessoas_pdis d ON d.id = o.pdi_id JOIN colaboradores col ON col.id = d.colaborador_id
            WHERE d.status = 'ativo' AND " . PessoasGestaoConfig::objetivoVencidoSql('o') . ' AND ' . $this->inClause('d.empresa_id', $empresaIds, $p, 'e') . $this->orgFilter($filters, $p, 'o');
        $objetivosVencidos = (int)($this->row($sql, $p)['n'] ?? 0);

        $media = $av['media'] ?? null;
        return [
            'colaboradores_ativos' => $colaboradoresAtivos,
            'avaliacoes' => [
                'pendentes' => (int)($av['pendentes'] ?? 0),
                'em_andamento' => (int)($av['em_andamento'] ?? 0),
                'finalizadas' => (int)($av['finalizadas'] ?? 0),
                'media' => $media !== null ? round((float)$media, 2) : null,
            ],
            'distribuicao' => $distribuicao,
            'gaps' => ['abertos' => (int)($gaps['abertos'] ?? 0), 'em_tratamento' => (int)($gaps['em_tratamento'] ?? 0), 'resolvidos' => (int)($gaps['resolvidos'] ?? 0)],
            'acoes' => [
                'pendentes' => (int)($acoes['pendentes'] ?? 0), 'em_andamento' => (int)($acoes['em_andamento'] ?? 0),
                'concluidas' => (int)($acoes['concluidas'] ?? 0), 'vencidas' => (int)($acoes['vencidas'] ?? 0),
            ],
            'encaminhadas' => $encaminhadas,
            'necessidades_pendentes' => $necessidadesPendentes,
            'pdis' => [
                'rascunho' => (int)($pdis['rascunho'] ?? 0), 'ativos' => (int)($pdis['ativos'] ?? 0),
                'concluidos' => (int)($pdis['concluidos'] ?? 0), 'ativos_atrasados' => (int)($pdis['ativos_atrasados'] ?? 0),
            ],
            'objetivos_vencidos' => $objetivosVencidos,
        ];
    }
}

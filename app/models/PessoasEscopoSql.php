<?php
namespace App\Models;

/**
 * Pilar de Pessoas - fragmentos SQL de escopo compartilhados entre a Visão
 * Geral (contadores) e as listagens operacionais (GAPs, Ações, Necessidades).
 * Usar a MESMA trait nos dois lados garante que "Dashboard = 4 ações
 * vencidas" e "listagem filtrada = 4" nunca divirjam por diferença de filtro.
 *
 * Requer $this->db e tenantInCondition() (BaseModel).
 */
trait PessoasEscopoSql
{
    /**
     * `coluna IN (empresas) AND tenant(coluna)`: empresas já resolvidas pelo
     * Controller + reforço de tenant no próprio SQL. Lista vazia = nada.
     *
     * @param int[] $ids
     */
    protected function inClause(string $column, array $ids, array &$params, string $prefix): string
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
    protected function orgFilter(array $filters, array &$params, string $prefix): string
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
     *
     * @return array{0:string,1:array}
     */
    protected function expandPlaceholders(string $sql, array $params): array
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
        return [$sql, $params];
    }

    protected function row(string $sql, array $params): array
    {
        [$sql, $params] = $this->expandPlaceholders($sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    protected function rows(string $sql, array $params): array
    {
        [$sql, $params] = $this->expandPlaceholders($sql, $params);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

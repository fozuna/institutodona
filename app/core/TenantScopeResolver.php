<?php
namespace App\Core;

use App\Database\Database;

class TenantScopeResolver
{
    public static function resolveForUser(array $user): array
    {
        $userId = isset($user['id']) ? (int)$user['id'] : 0;
        if ($userId > 0) {
            $assigned = self::assignedClientesForUser($userId);
            if (!empty($assigned)) {
                sort($assigned);
                return $assigned;
            }
        }
        $tipo = $user['tipo_acesso'] ?? null;
        if ($tipo === 'instituto') {
            return [];
        }
        $clienteId = isset($user['id_cliente']) ? (int)$user['id_cliente'] : 0;
        if ($clienteId <= 0) {
            return [];
        }
        return self::descendantsInclusive($clienteId);
    }

    public static function descendantsInclusive(int $rootClienteId): array
    {
        if ($rootClienteId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $hasRestrito = \App\Database\Database::columnExists('clientes', 'acesso_restrito');
        $selectAtivo = $hasAtivo ? 'ativo' : '1 AS ativo';
        $selectRestrito = $hasRestrito ? 'acesso_restrito' : '0 AS acesso_restrito';
        $stmt = $pdo->query("SELECT id, matriz_id, $selectAtivo, $selectRestrito FROM clientes");
        $rows = $stmt->fetchAll();
        $children = [];
        $flags = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $parent = isset($row['matriz_id']) ? (int)$row['matriz_id'] : 0;
            $flags[$id] = [
                'ativo' => (int)($row['ativo'] ?? 1) === 1,
                'restrito' => (int)($row['acesso_restrito'] ?? 0) === 1,
            ];
            if ($id <= 0 || $parent <= 0) {
                continue;
            }
            if (!isset($children[$parent])) {
                $children[$parent] = [];
            }
            $children[$parent][] = $id;
        }
        $visited = [];
        $stack = [$rootClienteId];
        while (!empty($stack)) {
            $current = (int)array_pop($stack);
            if ($current <= 0 || isset($visited[$current])) {
                continue;
            }
            $info = $flags[$current] ?? ['ativo' => true, 'restrito' => false];
            if (!$info['ativo'] || $info['restrito']) {
                continue;
            }
            $visited[$current] = true;
            foreach ($children[$current] ?? [] as $child) {
                $stack[] = (int)$child;
            }
        }
        $ids = array_map('intval', array_keys($visited));
        sort($ids);
        return $ids;
    }

    private static function assignedClientesForUser(int $userId): array
    {
        if (!\App\Database\Database::tableExists('usuario_empresas')) {
            return [];
        }
        $pdo = Database::getConnection();
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $hasRestrito = \App\Database\Database::columnExists('clientes', 'acesso_restrito');
        $where = [];
        if ($hasAtivo) {
            $where[] = 'c.ativo = 1';
        }
        if ($hasRestrito) {
            $where[] = 'c.acesso_restrito = 0';
        }
        $extra = empty($where) ? '' : (' AND ' . implode(' AND ', $where));
        $sql = 'SELECT ue.cliente_id
                FROM usuario_empresas ue
                JOIN clientes c ON c.id = ue.cliente_id
                WHERE ue.usuario_id = :uid AND ue.permitido = 1' . $extra . '
                ORDER BY ue.cliente_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll(), 'cliente_id'))));
    }
}

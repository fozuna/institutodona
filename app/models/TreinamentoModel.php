<?php
namespace App\Models;

use App\Core\Auth;

class TreinamentoModel extends BaseModel
{
    private const PERIODICIDADE_DIAS = [
        'avulso' => null,
        'mensal' => 30,
        'bimestral' => 60,
        'trimestral' => 90,
        'semestral' => 180,
        'anual' => 365,
        'bienal' => 730,
    ];

    private bool $schemaEnsured = false;

    private function ensureSchema(): void
    {
        if ($this->schemaEnsured) {
            return;
        }
        TreinamentoSchema::ensure($this->db);
        $this->schemaEnsured = true;
    }

    public static function periodicidadeOptions(): array
    {
        return [
            'avulso' => 'Avulso',
            'mensal' => 'Mensal',
            'bimestral' => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            'bienal' => 'Bienal',
        ];
    }

    public static function periodicidadeDias(?string $periodicidade): ?int
    {
        $key = strtolower(trim((string)$periodicidade));
        return self::PERIODICIDADE_DIAS[$key] ?? null;
    }

    public function all(array $filters = []): array
    {
        $this->ensureSchema();
        $params = [];
        $sql = "SELECT
                    t.*,
                    d.nome AS departamento_nome,
                    c.id AS cliente_id,
                    c.nome_empresa AS unidade_nome,
                    (SELECT COUNT(*) FROM treinamento_colaboradores tc WHERE tc.treinamento_id = t.id) AS total_colaboradores,
                    (SELECT COUNT(*) FROM treinamento_colaboradores tc WHERE tc.treinamento_id = t.id AND tc.status = 'concluido') AS total_concluidos,
                    (SELECT COUNT(*) FROM treinamentos_agenda ta WHERE ta.treinamento_id = t.id) AS total_agendamentos
                FROM treinamentos t
                JOIN departamentos d ON d.id = t.departamento_id
                JOIN clientes c ON c.id = d.cliente_id
                WHERE 1=1";

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND c.id = :cliente_id";
            $params['cliente_id'] = (int)$filters['cliente_id'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (t.nome LIKE :q OR t.objetivo LIKE :q OR t.fornecedor LIKE :q)";
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }

        $scope = $this->tenantInCondition('c.id', $params, 'trall');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }

        $sql .= " ORDER BY t.nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function create(array $data): int
    {
        $this->ensureSchema();
        $stmt = $this->db->prepare("INSERT INTO treinamentos
            (nome, objetivo, publico, carga_horaria, departamento_id, periodicidade, fornecedor)
            VALUES (:nome,:objetivo,:publico,:carga,:departamento,:periodicidade,:fornecedor)");
        $stmt->execute([
            'nome' => trim((string)$data['nome']),
            'objetivo' => trim((string)($data['objetivo'] ?? '')),
            'publico' => trim((string)($data['publico'] ?? '')),
            'carga' => $data['carga_horaria'] !== '' ? (float)$data['carga_horaria'] : null,
            'departamento' => (int)$data['departamento_id'],
            'periodicidade' => trim((string)($data['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($data['fornecedor'] ?? '')),
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->syncSetores($id, $data['setor_ids'] ?? []);
        $this->syncFuncoes($id, $data['funcao_ids'] ?? []);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureSchema();
        if (!$this->find($id)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE treinamentos
            SET nome = :nome,
                objetivo = :objetivo,
                publico = :publico,
                carga_horaria = :carga,
                departamento_id = :departamento,
                periodicidade = :periodicidade,
                fornecedor = :fornecedor
            WHERE id = :id");
        $ok = $stmt->execute([
            'id' => $id,
            'nome' => trim((string)$data['nome']),
            'objetivo' => trim((string)($data['objetivo'] ?? '')),
            'publico' => trim((string)($data['publico'] ?? '')),
            'carga' => $data['carga_horaria'] !== '' ? (float)$data['carga_horaria'] : null,
            'departamento' => (int)$data['departamento_id'],
            'periodicidade' => trim((string)($data['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($data['fornecedor'] ?? '')),
        ]);
        $this->syncSetores($id, $data['setor_ids'] ?? []);
        $this->syncFuncoes($id, $data['funcao_ids'] ?? []);
        return $ok;
    }

    public function delete(int $id): bool
    {
        $this->ensureSchema();
        if (!$this->find($id)) {
            return false;
        }
        return $this->db->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $id]);
    }

    public function find(int $id): ?array
    {
        $this->ensureSchema();
        $params = ['id' => $id];
        $sql = "SELECT
                t.*,
                d.nome AS departamento_nome,
                d.cliente_id,
                c.nome_empresa AS unidade_nome
            FROM treinamentos t
            JOIN departamentos d ON d.id = t.departamento_id
            JOIN clientes c ON c.id = d.cliente_id
            WHERE t.id = :id";
        $scope = $this->tenantInCondition('c.id', $params, 'trfind');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['setor_ids'] = $this->idsFor('treinamento_setores', 'setor_id', $id);
        $row['funcao_ids'] = $this->idsFor('treinamento_funcoes', 'funcao_id', $id);
        $row['setores'] = $this->setoresForTreinamento($id);
        $row['funcoes'] = $this->funcoesForTreinamento($id);
        return $row;
    }

    public function syncColaboradores(int $treinamentoId, array $colaboradorIds): int
    {
        $this->ensureSchema();
        if (!$this->find($treinamentoId)) {
            return 0;
        }
        $this->refreshStatuses($treinamentoId);
        $ids = array_values(array_unique(array_filter(array_map('intval', $colaboradorIds))));
        $added = 0;
        $stmt = $this->db->prepare("INSERT INTO treinamento_colaboradores (treinamento_id, colaborador_id, status)
            VALUES (:treinamento_id,:colaborador_id,'pendente')
            ON DUPLICATE KEY UPDATE treinamento_id = treinamento_id");
        foreach ($ids as $colaboradorId) {
            $stmt->execute([
                'treinamento_id' => $treinamentoId,
                'colaborador_id' => $colaboradorId,
            ]);
            if ($stmt->rowCount() > 0) {
                $added++;
            }
        }
        return $added;
    }

    public function unlinkColaborador(int $treinamentoId, int $colaboradorId): bool
    {
        $this->ensureSchema();
        if (!$this->find($treinamentoId)) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM treinamento_colaboradores
            WHERE treinamento_id = :treinamento_id
              AND colaborador_id = :colaborador_id
              AND status = 'pendente'");
        return $stmt->execute([
            'treinamento_id' => $treinamentoId,
            'colaborador_id' => $colaboradorId,
        ]);
    }

    public function linkedColaboradores(int $treinamentoId, ?string $status = null): array
    {
        $this->ensureSchema();
        if (!$this->find($treinamentoId)) {
            return [];
        }
        $this->refreshStatuses($treinamentoId);
        $params = ['id' => $treinamentoId];
        $sql = "SELECT
                    tc.*,
                    col.nome AS colaborador_nome,
                    col.email AS colaborador_email,
                    f.nome AS funcao_nome,
                    s.nome AS setor_nome,
                    c.nome_empresa AS unidade_nome,
                    (
                        SELECT MAX(ta.data)
                        FROM treinamento_participantes tp
                        JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id
                        WHERE tp.colaborador_id = tc.colaborador_id
                          AND ta.treinamento_id = tc.treinamento_id
                          AND tp.presenca = 1
                          AND tp.certificado_emitido = 1
                    ) AS ultima_conclusao
                FROM treinamento_colaboradores tc
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                LEFT JOIN clientes c ON c.id = col.cliente_id
                WHERE tc.treinamento_id = :id";
        if ($status) {
            $sql .= " AND tc.status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY col.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function availableColaboradores(int $treinamentoId): array
    {
        $this->ensureSchema();
        $training = $this->find($treinamentoId);
        if (!$training) {
            return [];
        }
        $params = ['cliente_id' => (int)$training['cliente_id']];
        $sql = "SELECT col.id, col.nome, col.email, f.nome AS funcao_nome, s.nome AS setor_nome
                FROM colaboradores col
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                WHERE col.cliente_id = :cliente_id
                ORDER BY col.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function pendingAlerts(?int $treinamentoId = null): array
    {
        $this->ensureSchema();
        $this->refreshStatuses($treinamentoId);
        $params = [];
        $sql = "SELECT
                    t.id AS treinamento_id,
                    t.nome AS treinamento_nome,
                    t.periodicidade,
                    tc.colaborador_id,
                    col.nome AS colaborador_nome,
                    c.nome_empresa AS unidade_nome,
                    (
                        SELECT MAX(ta.data)
                        FROM treinamento_participantes tp
                        JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id
                        WHERE tp.colaborador_id = tc.colaborador_id
                          AND ta.treinamento_id = tc.treinamento_id
                          AND tp.presenca = 1
                          AND tp.certificado_emitido = 1
                    ) AS ultima_conclusao
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                JOIN clientes c ON c.id = col.cliente_id
                JOIN departamentos d ON d.id = t.departamento_id
                WHERE 1=1";
        if ($treinamentoId) {
            $sql .= " AND t.id = :treinamento_id";
            $params['treinamento_id'] = $treinamentoId;
        }
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'tralert');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $alerts = [];
        $today = strtotime(date('Y-m-d'));
        foreach ($rows as $row) {
            $days = self::periodicidadeDias($row['periodicidade'] ?? null);
            if ($days === null) {
                continue;
            }
            $last = !empty($row['ultima_conclusao']) ? strtotime((string)$row['ultima_conclusao']) : null;
            if (!$last) {
                $alerts[] = array_merge($row, ['alerta' => 'pendente', 'dias_restantes' => null]);
                continue;
            }
            $expires = strtotime('+' . $days . ' days', $last);
            $remaining = (int)floor(($expires - $today) / 86400);
            if ($remaining <= 30) {
                $alerts[] = array_merge($row, [
                    'alerta' => $remaining < 0 ? 'vencido' : 'proximo_vencimento',
                    'dias_restantes' => $remaining,
                ]);
            }
        }
        return $alerts;
    }

    public function dashboard(): array
    {
        $this->ensureSchema();
        $this->refreshStatuses();
        return [
            'por_treinamento' => $this->dashboardBy('t.id', 't.nome'),
            'por_funcao' => $this->dashboardBy('f.id', 'f.nome'),
            'por_unidade' => $this->dashboardBy('c.id', 'c.nome_empresa'),
            'pendentes' => $this->dashboardListByStatus('pendente'),
            'concluidos' => $this->dashboardListByStatus('concluido'),
            'alertas' => $this->pendingAlerts(),
        ];
    }

    public function refreshStatuses(?int $treinamentoId = null): void
    {
        $this->ensureSchema();
        $params = [];
        $sql = "SELECT tc.id, tc.treinamento_id, t.periodicidade,
                       (
                           SELECT MAX(ta.data)
                           FROM treinamento_participantes tp
                           JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id
                           WHERE tp.colaborador_id = tc.colaborador_id
                             AND ta.treinamento_id = tc.treinamento_id
                             AND tp.presenca = 1
                             AND tp.certificado_emitido = 1
                       ) AS ultima_conclusao
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                WHERE 1=1";
        if ($treinamentoId) {
            $sql .= " AND tc.treinamento_id = :treinamento_id";
            $params['treinamento_id'] = $treinamentoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        $update = $this->db->prepare("UPDATE treinamento_colaboradores SET status = :status WHERE id = :id");
        $today = strtotime(date('Y-m-d'));
        foreach ($rows as $row) {
            $status = 'pendente';
            $last = !empty($row['ultima_conclusao']) ? strtotime((string)$row['ultima_conclusao']) : null;
            if ($last) {
                $days = self::periodicidadeDias($row['periodicidade'] ?? null);
                if ($days === null || strtotime('+' . $days . ' days', $last) >= $today) {
                    $status = 'concluido';
                }
            }
            $update->execute(['status' => $status, 'id' => (int)$row['id']]);
        }
    }

    private function dashboardBy(string $groupExpr, string $labelExpr): array
    {
        $params = [];
        $sql = "SELECT
                    {$groupExpr} AS group_id,
                    {$labelExpr} AS group_label,
                    COUNT(*) AS total,
                    SUM(CASE WHEN tc.status = 'concluido' THEN 1 ELSE 0 END) AS concluidos
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN clientes c ON c.id = col.cliente_id
                JOIN departamentos d ON d.id = t.departamento_id
                WHERE 1=1";
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'trdash');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " GROUP BY {$groupExpr}, {$labelExpr} ORDER BY {$labelExpr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $total = max(1, (int)$row['total']);
            $row['percentual'] = round(((int)$row['concluidos'] / $total) * 100, 1);
        }
        return $rows;
    }

    private function dashboardListByStatus(string $status): array
    {
        $params = ['status' => $status];
        $sql = "SELECT
                    tc.*,
                    t.nome AS treinamento_nome,
                    col.nome AS colaborador_nome,
                    c.nome_empresa AS unidade_nome
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                JOIN clientes c ON c.id = col.cliente_id
                JOIN departamentos d ON d.id = t.departamento_id
                WHERE tc.status = :status";
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'trlist');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " ORDER BY t.nome, col.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function idsFor(string $table, string $column, int $treinamentoId): array
    {
        $stmt = $this->db->prepare("SELECT {$column} FROM {$table} WHERE treinamento_id = :id");
        $stmt->execute(['id' => $treinamentoId]);
        return array_map('intval', array_column($stmt->fetchAll() ?: [], $column));
    }

    private function syncSetores(int $treinamentoId, array $ids): void
    {
        $this->db->prepare('DELETE FROM treinamento_setores WHERE treinamento_id = :id')->execute(['id' => $treinamentoId]);
        $stmt = $this->db->prepare('INSERT INTO treinamento_setores (treinamento_id, setor_id) VALUES (:treinamento_id,:setor_id)');
        foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
            $stmt->execute(['treinamento_id' => $treinamentoId, 'setor_id' => $id]);
        }
    }

    private function syncFuncoes(int $treinamentoId, array $ids): void
    {
        $this->db->prepare('DELETE FROM treinamento_funcoes WHERE treinamento_id = :id')->execute(['id' => $treinamentoId]);
        $stmt = $this->db->prepare('INSERT INTO treinamento_funcoes (treinamento_id, funcao_id) VALUES (:treinamento_id,:funcao_id)');
        foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
            $stmt->execute(['treinamento_id' => $treinamentoId, 'funcao_id' => $id]);
        }
    }

    private function setoresForTreinamento(int $treinamentoId): array
    {
        $stmt = $this->db->prepare("SELECT s.* FROM treinamento_setores ts JOIN setores s ON s.id = ts.setor_id WHERE ts.treinamento_id = :id ORDER BY s.nome");
        $stmt->execute(['id' => $treinamentoId]);
        return $stmt->fetchAll() ?: [];
    }

    private function funcoesForTreinamento(int $treinamentoId): array
    {
        $stmt = $this->db->prepare("SELECT f.* FROM treinamento_funcoes tf JOIN funcoes f ON f.id = tf.funcao_id WHERE tf.treinamento_id = :id ORDER BY f.nome");
        $stmt->execute(['id' => $treinamentoId]);
        return $stmt->fetchAll() ?: [];
    }
}

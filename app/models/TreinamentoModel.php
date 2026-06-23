<?php
namespace App\Models;

use App\Core\Auth;
use App\Database\Database;

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

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    private function validSetorIdsForDepartamento(array $ids, int $departamentoId): array
    {
        $valid = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
            if ($this->setorBelongsToCatalogCliente($id, null, $departamentoId)) {
                $valid[] = $id;
            }
        }
        return $valid;
    }

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    private function validFuncaoIdsForDepartamento(array $ids, int $departamentoId): array
    {
        $valid = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
            if ($this->funcaoBelongsToCatalogCliente($id, null, null, $departamentoId)) {
                $valid[] = $id;
            }
        }
        return $valid;
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
        $departamentoId = (int)($data['departamento_id'] ?? 0);
        if (!$this->departamentoBelongsToCatalogCliente($departamentoId)) {
            return 0;
        }
        $requestedSetorIds = array_values(array_unique(array_filter(array_map('intval', (array)($data['setor_ids'] ?? [])))));
        $requestedFuncaoIds = array_values(array_unique(array_filter(array_map('intval', (array)($data['funcao_ids'] ?? [])))));
        $setorIds = $this->validSetorIdsForDepartamento($requestedSetorIds, $departamentoId);
        $funcaoIds = $this->validFuncaoIdsForDepartamento($requestedFuncaoIds, $departamentoId);
        if (count($setorIds) !== count($requestedSetorIds) || count($funcaoIds) !== count($requestedFuncaoIds)) {
            return 0;
        }
        $stmt = $this->db->prepare("INSERT INTO treinamentos
            (nome, objetivo, publico, carga_horaria, departamento_id, periodicidade, fornecedor, tipo_treinamento, template_certificado, assinatura_responsavel)
            VALUES (:nome,:objetivo,:publico,:carga,:departamento,:periodicidade,:fornecedor,:tipo_treinamento,:template_certificado,:assinatura_responsavel)");
        $stmt->execute([
            'nome' => trim((string)$data['nome']),
            'objetivo' => trim((string)($data['objetivo'] ?? '')),
            'publico' => trim((string)($data['publico'] ?? '')),
            'carga' => $data['carga_horaria'] !== '' ? (float)$data['carga_horaria'] : null,
            'departamento' => $departamentoId,
            'periodicidade' => trim((string)($data['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($data['fornecedor'] ?? '')),
            'tipo_treinamento' => trim((string)($data['tipo_treinamento'] ?? '')),
            'template_certificado' => trim((string)($data['template_certificado'] ?? '')),
            'assinatura_responsavel' => trim((string)($data['assinatura_responsavel'] ?? '')),
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->syncSetores($id, $setorIds);
        $this->syncFuncoes($id, $funcaoIds);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureSchema();
        if (!$this->find($id)) {
            return false;
        }
        $departamentoId = (int)($data['departamento_id'] ?? 0);
        if (!$this->departamentoBelongsToCatalogCliente($departamentoId)) {
            return false;
        }
        $requestedSetorIds = array_values(array_unique(array_filter(array_map('intval', (array)($data['setor_ids'] ?? [])))));
        $requestedFuncaoIds = array_values(array_unique(array_filter(array_map('intval', (array)($data['funcao_ids'] ?? [])))));
        $setorIds = $this->validSetorIdsForDepartamento($requestedSetorIds, $departamentoId);
        $funcaoIds = $this->validFuncaoIdsForDepartamento($requestedFuncaoIds, $departamentoId);
        if (count($setorIds) !== count($requestedSetorIds) || count($funcaoIds) !== count($requestedFuncaoIds)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE treinamentos
            SET nome = :nome,
                objetivo = :objetivo,
                publico = :publico,
                carga_horaria = :carga,
                departamento_id = :departamento,
                periodicidade = :periodicidade,
                fornecedor = :fornecedor,
                tipo_treinamento = :tipo_treinamento,
                template_certificado = :template_certificado,
                assinatura_responsavel = :assinatura_responsavel
            WHERE id = :id");
        $ok = $stmt->execute([
            'id' => $id,
            'nome' => trim((string)$data['nome']),
            'objetivo' => trim((string)($data['objetivo'] ?? '')),
            'publico' => trim((string)($data['publico'] ?? '')),
            'carga' => $data['carga_horaria'] !== '' ? (float)$data['carga_horaria'] : null,
            'departamento' => $departamentoId,
            'periodicidade' => trim((string)($data['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($data['fornecedor'] ?? '')),
            'tipo_treinamento' => trim((string)($data['tipo_treinamento'] ?? '')),
            'template_certificado' => trim((string)($data['template_certificado'] ?? '')),
            'assinatura_responsavel' => trim((string)($data['assinatura_responsavel'] ?? '')),
        ]);
        $this->syncSetores($id, $setorIds);
        $this->syncFuncoes($id, $funcaoIds);
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

    public function syncSelectedColaboradores(int $treinamentoId, array $colaboradorIds): int
    {
        $this->ensureSchema();
        if (!$this->find($treinamentoId)) {
            return 0;
        }

        $selectedIds = array_values(array_unique(array_filter(array_map('intval', $colaboradorIds))));
        $added = $this->syncColaboradores($treinamentoId, $selectedIds);

        $params = ['treinamento_id' => $treinamentoId];
        $sql = "DELETE FROM treinamento_colaboradores
                WHERE treinamento_id = :treinamento_id
                  AND status = 'pendente'";
        if (!empty($selectedIds)) {
            $holders = [];
            foreach ($selectedIds as $index => $colaboradorId) {
                $key = 'colaborador_' . $index;
                $holders[] = ':' . $key;
                $params[$key] = $colaboradorId;
            }
            $sql .= " AND colaborador_id NOT IN (" . implode(',', $holders) . ")";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
                          AND (tp.presenca = 1 OR tp.certificado_emitido = 1)
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
        return array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'nome' => $row['nome'],
                'email' => $row['email_corporativo'],
                'funcao_nome' => $row['cargo'],
                'setor_nome' => $row['setor'],
            ];
        }, $this->eligibleColaboradoresForTraining($treinamentoId));
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
                          AND (tp.presenca = 1 OR tp.certificado_emitido = 1)
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
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'tralert');
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

    public function dashboard(array $filters = []): array
    {
        $this->ensureSchema();
        $this->refreshStatuses();
        $participacao = $this->participationByTraining($filters);
        $setores = $this->sectorTotals($filters);
        $acumulados = $this->periodAccumulators($filters);
        $alertasSetor = array_values(array_filter($setores, static fn(array $row): bool => (float)($row['percentual_participacao'] ?? 0) < 50.0));
        return [
            'por_treinamento' => $this->dashboardBy($filters, 't.id', 't.nome'),
            'por_funcao' => $this->dashboardBy($filters, 'f.id', 'f.nome'),
            'por_unidade' => $this->dashboardBy($filters, 'c.id', 'c.nome_empresa'),
            'pendentes' => $this->dashboardListByStatus($filters, 'pendente'),
            'concluidos' => $this->dashboardListByStatus($filters, 'concluido'),
            'alertas' => $this->pendingAlertsFiltered($filters),
            'participacao_treinamento' => $participacao,
            'setores' => $setores,
            'acumulados' => $acumulados,
            'alertas_setor' => $alertasSetor,
            'resumo' => [
                'treinamentos_monitorados' => count($participacao),
                'setores_monitorados' => count($setores),
                'total_inscritos' => array_sum(array_map(static fn(array $row): int => (int)($row['total_inscritos'] ?? 0), $participacao)),
                'total_presentes' => array_sum(array_map(static fn(array $row): int => (int)($row['total_presentes'] ?? 0), $participacao)),
                'total_certificados' => array_sum(array_map(static fn(array $row): int => (int)($row['total_certificados'] ?? 0), $participacao)),
            ],
            'filters' => $filters,
        ];
    }

    public function refreshStatuses(?int $treinamentoId = null): void
    {
        $this->ensureSchema();
        $params = [];
        $sql = "SELECT
                    tc.id,
                    tc.treinamento_id,
                    tc.colaborador_id,
                    tc.status AS status_atual,
                    tc.status_detalhe AS status_detalhe_atual,
                    t.periodicidade,
                    t.carga_horaria,
                    COUNT(ta.id) AS total_agendas_unidade,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) <= NOW() THEN 1 ELSE 0 END) AS total_agendas_encerradas,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) > NOW() THEN 1 ELSE 0 END) AS total_agendas_futuras,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) <= NOW()
                              AND (tp.presenca = 0 AND tp.certificado_emitido = 0)
                             THEN 1 ELSE 0 END) AS pendencias_encerradas,
                    SUM(CASE WHEN ta.id IS NOT NULL
                              AND (tp.presenca = 1 OR tp.certificado_emitido = 1)
                              AND COALESCE(ta.data_fim, ta.data) <= NOW()
                             THEN 1 ELSE 0 END) AS agendas_com_presenca,
                    MAX(CASE WHEN ta.id IS NOT NULL AND (tp.presenca = 1 OR tp.certificado_emitido = 1) THEN ta.data ELSE NULL END) AS ultima_conclusao,
                    COALESCE(SUM(CASE
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND tp.hora_entrada IS NOT NULL
                         AND tp.hora_saida IS NOT NULL
                        THEN GREATEST(0, TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT(DATE(ta.data), ' ', tp.hora_entrada),
                            CONCAT(DATE(COALESCE(ta.data_fim, ta.data)), ' ', tp.hora_saida)
                        ))
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND ta.data_fim IS NOT NULL
                        THEN GREATEST(0, TIMESTAMPDIFF(MINUTE, ta.data, ta.data_fim))
                        ELSE 0 END), 0) AS minutos_presenca,
                    SUM(CASE
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND tp.hora_entrada IS NULL
                         AND tp.hora_saida IS NULL
                         AND ta.data_fim IS NULL
                        THEN 1 ELSE 0 END) AS minutos_desconhecidos
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN treinamento_participantes tp
                  ON tp.colaborador_id = tc.colaborador_id
                LEFT JOIN treinamentos_agenda ta
                  ON ta.id = tp.agenda_id
                 AND ta.treinamento_id = tc.treinamento_id
                WHERE 1=1";
        if ($treinamentoId) {
            $sql .= " AND tc.treinamento_id = :treinamento_id";
            $params['treinamento_id'] = $treinamentoId;
        }
        $sql .= " GROUP BY tc.id, tc.treinamento_id, tc.colaborador_id, tc.status, tc.status_detalhe, t.periodicidade, t.carga_horaria";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $update = $this->db->prepare("UPDATE treinamento_colaboradores
            SET status = :status,
                status_detalhe = :status_detalhe
            WHERE id = :id");

        $today = strtotime(date('Y-m-d'));
        foreach ($rows as $row) {
            $computed = $this->computeStatusFromSummaryRow($row, $today);
            $currentStatus = (string)($row['status_atual'] ?? '');
            $currentDetail = (string)($row['status_detalhe_atual'] ?? '');
            if ($currentStatus === $computed['status'] && $currentDetail === $computed['status_detalhe']) {
                continue;
            }
            $update->execute([
                'id' => (int)$row['id'],
                'status' => $computed['status'],
                'status_detalhe' => $computed['status_detalhe'] !== '' ? $computed['status_detalhe'] : null,
            ]);
        }
    }

    private function computeStatusFromSummaryRow(array $row, int $todayTs): array
    {
        $totalAgendas = (int)($row['total_agendas_unidade'] ?? 0);
        $future = (int)($row['total_agendas_futuras'] ?? 0);
        $pendingPast = (int)($row['pendencias_encerradas'] ?? 0);
        $attended = (int)($row['agendas_com_presenca'] ?? 0);
        $minutes = (int)($row['minutos_presenca'] ?? 0);
        $unknownMinutes = (int)($row['minutos_desconhecidos'] ?? 0);
        $carga = $row['carga_horaria'] !== null ? (float)$row['carga_horaria'] : 0.0;
        $requiredMinutes = $carga > 0 ? (int)round($carga * 60) : 0;

        $detail = 'pendente_inicio';
        if ($totalAgendas > 0) {
            if ($attended > 0) {
                $detail = 'em_andamento';
            }
            if ($pendingPast > 0) {
                $detail = 'interrompido';
            }
        }

        $eligibleByAgenda = $totalAgendas > 0 && $future === 0 && $pendingPast === 0 && $attended > 0;
        $eligibleByHours = true;
        if ($requiredMinutes > 0) {
            $eligibleByHours = $unknownMinutes === 0 && $minutes >= $requiredMinutes;
        }
        $eligible = $eligibleByAgenda && $eligibleByHours;

        if ($eligible) {
            $last = !empty($row['ultima_conclusao']) ? strtotime((string)$row['ultima_conclusao']) : null;
            $days = self::periodicidadeDias($row['periodicidade'] ?? null);
            if ($days !== null && $last) {
                if (strtotime('+' . $days . ' days', $last) < $todayTs) {
                    return ['status' => 'pendente', 'status_detalhe' => 'interrompido'];
                }
            }
            return ['status' => 'concluido', 'status_detalhe' => 'concluido'];
        }
        return ['status' => 'pendente', 'status_detalhe' => $detail];
    }

    public function reconcileStatuses(bool $apply = true, ?int $treinamentoId = null): array
    {
        $this->ensureSchema();
        $params = [];
        $sql = "SELECT
                    tc.id,
                    tc.treinamento_id,
                    tc.colaborador_id,
                    tc.status AS status_atual,
                    tc.status_detalhe AS status_detalhe_atual,
                    t.nome AS treinamento_nome,
                    t.periodicidade,
                    t.carga_horaria,
                    col.nome AS colaborador_nome,
                    col.email AS colaborador_email,
                    COUNT(ta.id) AS total_agendas_unidade,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) <= NOW() THEN 1 ELSE 0 END) AS total_agendas_encerradas,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) > NOW() THEN 1 ELSE 0 END) AS total_agendas_futuras,
                    SUM(CASE WHEN ta.id IS NOT NULL AND COALESCE(ta.data_fim, ta.data) <= NOW()
                              AND (tp.presenca = 0 AND tp.certificado_emitido = 0)
                             THEN 1 ELSE 0 END) AS pendencias_encerradas,
                    SUM(CASE WHEN ta.id IS NOT NULL
                              AND (tp.presenca = 1 OR tp.certificado_emitido = 1)
                              AND COALESCE(ta.data_fim, ta.data) <= NOW()
                             THEN 1 ELSE 0 END) AS agendas_com_presenca,
                    MAX(CASE WHEN ta.id IS NOT NULL AND (tp.presenca = 1 OR tp.certificado_emitido = 1) THEN ta.data ELSE NULL END) AS ultima_conclusao,
                    COALESCE(SUM(CASE
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND tp.hora_entrada IS NOT NULL
                         AND tp.hora_saida IS NOT NULL
                        THEN GREATEST(0, TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT(DATE(ta.data), ' ', tp.hora_entrada),
                            CONCAT(DATE(COALESCE(ta.data_fim, ta.data)), ' ', tp.hora_saida)
                        ))
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND ta.data_fim IS NOT NULL
                        THEN GREATEST(0, TIMESTAMPDIFF(MINUTE, ta.data, ta.data_fim))
                        ELSE 0 END), 0) AS minutos_presenca,
                    SUM(CASE
                        WHEN ta.id IS NOT NULL
                         AND tp.presenca = 1
                         AND tp.hora_entrada IS NULL
                         AND tp.hora_saida IS NULL
                         AND ta.data_fim IS NULL
                        THEN 1 ELSE 0 END) AS minutos_desconhecidos
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN treinamento_participantes tp
                  ON tp.colaborador_id = tc.colaborador_id
                LEFT JOIN treinamentos_agenda ta
                  ON ta.id = tp.agenda_id
                 AND ta.treinamento_id = tc.treinamento_id
                WHERE 1=1";
        if ($treinamentoId) {
            $sql .= " AND tc.treinamento_id = :treinamento_id";
            $params['treinamento_id'] = $treinamentoId;
        }
        $scope = $this->tenantInCondition('col.cliente_id', $params, 'trrecon');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " GROUP BY
                    tc.id, tc.treinamento_id, tc.colaborador_id, tc.status, tc.status_detalhe,
                    t.nome, t.periodicidade, t.carga_horaria,
                    col.nome, col.email
                  ORDER BY tc.treinamento_id, tc.colaborador_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $today = strtotime(date('Y-m-d'));
        $changes = [];
        foreach ($rows as $row) {
            $computed = $this->computeStatusFromSummaryRow($row, $today);
            $currentStatus = (string)($row['status_atual'] ?? '');
            $currentDetail = (string)($row['status_detalhe_atual'] ?? '');
            if ($currentStatus === $computed['status'] && $currentDetail === $computed['status_detalhe']) {
                continue;
            }
            $carga = $row['carga_horaria'] !== null ? (float)$row['carga_horaria'] : 0.0;
            $requiredMinutes = $carga > 0 ? (int)round($carga * 60) : 0;
            $changes[] = [
                'tc_id' => (int)$row['id'],
                'treinamento_id' => (int)$row['treinamento_id'],
                'treinamento_nome' => (string)($row['treinamento_nome'] ?? ''),
                'colaborador_id' => (int)$row['colaborador_id'],
                'colaborador_nome' => (string)($row['colaborador_nome'] ?? ''),
                'colaborador_email' => (string)($row['colaborador_email'] ?? ''),
                'status_atual' => $currentStatus,
                'status_detalhe_atual' => $currentDetail,
                'status_esperado' => $computed['status'],
                'status_detalhe_esperado' => $computed['status_detalhe'],
                'evidencias' => [
                    'total_agendas_unidade' => (int)($row['total_agendas_unidade'] ?? 0),
                    'agendas_encerradas' => (int)($row['total_agendas_encerradas'] ?? 0),
                    'agendas_futuras' => (int)($row['total_agendas_futuras'] ?? 0),
                    'pendencias_encerradas' => (int)($row['pendencias_encerradas'] ?? 0),
                    'agendas_com_presenca' => (int)($row['agendas_com_presenca'] ?? 0),
                    'minutos_presenca' => (int)($row['minutos_presenca'] ?? 0),
                    'minutos_desconhecidos' => (int)($row['minutos_desconhecidos'] ?? 0),
                    'carga_horaria_horas' => $carga > 0 ? $carga : null,
                    'carga_horaria_minutos' => $requiredMinutes > 0 ? $requiredMinutes : null,
                    'ultima_conclusao' => (string)($row['ultima_conclusao'] ?? ''),
                    'periodicidade' => (string)($row['periodicidade'] ?? ''),
                ],
            ];
        }

        if (!$apply || empty($changes)) {
            return [
                'apply' => $apply,
                'total_lidos' => count($rows),
                'total_inconsistencias' => count($changes),
                'alteracoes' => $changes,
            ];
        }

        $this->db->beginTransaction();
        try {
            $update = $this->db->prepare("UPDATE treinamento_colaboradores
                SET status = :status,
                    status_detalhe = :status_detalhe
                WHERE id = :id");
            foreach ($changes as $change) {
                $update->execute([
                    'id' => (int)$change['tc_id'],
                    'status' => $change['status_esperado'],
                    'status_detalhe' => $change['status_detalhe_esperado'] !== '' ? $change['status_detalhe_esperado'] : null,
                ]);
                $this->audit('status_reconcile', [
                    'treinamento_id' => (int)$change['treinamento_id'],
                    'colaborador_id' => (int)$change['colaborador_id'],
                    'status_from' => $change['status_atual'],
                    'status_to' => $change['status_esperado'],
                    'detail_from' => $change['status_detalhe_atual'],
                    'detail_to' => $change['status_detalhe_esperado'],
                    'evidencias' => $change['evidencias'],
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'apply' => $apply,
            'total_lidos' => count($rows),
            'total_inconsistencias' => count($changes),
            'alteracoes' => $changes,
        ];
    }

    private function audit(string $acao, array $payload): void
    {
        $stmt = $this->db->prepare("INSERT INTO treinamento_auditoria_logs
            (treinamento_id, agenda_id, participante_id, colaborador_id, acao, detalhes_json, created_by)
            VALUES (:treinamento_id, :agenda_id, :participante_id, :colaborador_id, :acao, :detalhes_json, :created_by)");
        $stmt->execute([
            'treinamento_id' => $payload['treinamento_id'] ?? null,
            'agenda_id' => $payload['agenda_id'] ?? null,
            'participante_id' => $payload['participante_id'] ?? null,
            'colaborador_id' => $payload['colaborador_id'] ?? null,
            'acao' => $acao,
            'detalhes_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_by' => (int)(Auth::user()['id'] ?? 0) ?: null,
        ]);
    }

    private function dashboardBy(array $filters, string $groupExpr, string $labelExpr): array
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
        $sql .= $this->applyEmpresaDashboardFilter($filters, $params, ['d.cliente_id', 'col.cliente_id']);
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'trdash');
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

    private function dashboardListByStatus(array $filters, string $status): array
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
        $sql .= $this->applyEmpresaDashboardFilter($filters, $params, ['d.cliente_id', 'col.cliente_id', 'c.id']);
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'trlist');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " ORDER BY t.nome, col.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function pendingAlertsFiltered(array $filters): array
    {
        $this->ensureSchema();
        $this->refreshStatuses();
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
                          AND (tp.presenca = 1 OR tp.certificado_emitido = 1)
                    ) AS ultima_conclusao
                FROM treinamento_colaboradores tc
                JOIN treinamentos t ON t.id = tc.treinamento_id
                JOIN colaboradores col ON col.id = tc.colaborador_id
                JOIN clientes c ON c.id = col.cliente_id
                JOIN departamentos d ON d.id = t.departamento_id
                WHERE 1=1";
        $sql .= $this->applyEmpresaDashboardFilter($filters, $params, ['d.cliente_id', 'col.cliente_id', 'c.id']);
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'tralert');
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

    public function eligibleColaboradoresForTraining(int $treinamentoId, array $filters = []): array
    {
        $this->ensureSchema();
        $treinamento = $this->find($treinamentoId);
        if (!$treinamento) {
            return [];
        }

        $cacheKey = 'treinamento_elegiveis_' . md5(json_encode([$treinamentoId, $filters], JSON_UNESCAPED_UNICODE));
        $cached = $this->cacheGet($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [
            'treinamento_id' => $treinamentoId,
            'cliente_id' => (int)$treinamento['cliente_id'],
        ];
        $statusExpr = "CASE
            WHEN COALESCE(NULLIF(TRIM(col.status_atual), ''), 'ativo') = 'ativo' THEN 'Elegivel'
            ELSE 'Inelegivel'
        END";
        $histJoin = "LEFT JOIN (
                    SELECT
                        tp.colaborador_id,
                        MAX(DATE(ta.data)) AS ultima_conclusao,
                        COUNT(*) AS total_conclusoes
                    FROM treinamento_participantes tp
                    JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id
                    WHERE tp.presenca = 1 OR tp.certificado_emitido = 1
                    GROUP BY tp.colaborador_id
                ) th ON th.colaborador_id = col.id";
        $sql = "SELECT
                    col.id,
                    col.nome,
                    col.matricula,
                    s.nome AS setor,
                    f.nome AS cargo,
                    col.cpf,
                    col.email AS email_corporativo,
                    COALESCE(NULLIF(TRIM(col.status_atual), ''), 'ativo') AS status_atual,
                    col.data_admissao,
                    {$statusExpr} AS status_elegibilidade,
                    CASE WHEN tc.colaborador_id IS NULL THEN 0 ELSE 1 END AS pre_cadastrado,
                    th.ultima_conclusao,
                    th.total_conclusoes
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN treinamento_colaboradores tc
                    ON tc.treinamento_id = :treinamento_id
                   AND tc.colaborador_id = col.id
                {$histJoin}
                WHERE col.cliente_id = :cliente_id";
        $allowedSetorIds = array_values(array_unique(array_filter(array_map('intval', (array)($treinamento['setor_ids'] ?? [])))));
        $allowedFuncaoIds = array_values(array_unique(array_filter(array_map('intval', (array)($treinamento['funcao_ids'] ?? [])))));

        $departamentoIds = array_values(array_unique(array_filter(array_map('intval', (array)($filters['departamento_ids'] ?? [])))));
        $setorIds = array_values(array_unique(array_filter(array_map('intval', (array)($filters['setor_ids'] ?? [])))));
        $funcaoIds = array_values(array_unique(array_filter(array_map('intval', (array)($filters['funcao_ids'] ?? [])))));

        if (empty($setorIds) && !empty($filters['setor_id'])) {
            $setorIds = [(int)$filters['setor_id']];
        }
        if (empty($funcaoIds) && !empty($filters['funcao_id'])) {
            $funcaoIds = [(int)$filters['funcao_id']];
        }

        if (!empty($allowedSetorIds)) {
            $setorIds = empty($setorIds) ? $allowedSetorIds : array_values(array_intersect($setorIds, $allowedSetorIds));
        }
        if (!empty($allowedFuncaoIds)) {
            $funcaoIds = empty($funcaoIds) ? $allowedFuncaoIds : array_values(array_intersect($funcaoIds, $allowedFuncaoIds));
        }

        if (!empty($departamentoIds)) {
            $holders = [];
            foreach (array_values($departamentoIds) as $i => $id) {
                $k = 'dep_' . $i;
                $holders[] = ':' . $k;
                $params[$k] = (int)$id;
            }
            if (Database::columnExists('colaboradores', 'departamento_id')) {
                $sql .= " AND col.departamento_id IN (" . implode(',', $holders) . ")";
            } else {
                $sql .= " AND d.id IN (" . implode(',', $holders) . ")";
            }
        }
        if (!empty($setorIds)) {
            $holders = [];
            foreach (array_values($setorIds) as $i => $id) {
                $k = 'set_' . $i;
                $holders[] = ':' . $k;
                $params[$k] = (int)$id;
            }
            $sql .= " AND s.id IN (" . implode(',', $holders) . ")";
        }
        if (!empty($funcaoIds)) {
            $holders = [];
            foreach (array_values($funcaoIds) as $i => $id) {
                $k = 'fun_' . $i;
                $holders[] = ':' . $k;
                $params[$k] = (int)$id;
            }
            $sql .= " AND f.id IN (" . implode(',', $holders) . ")";
        }
        $colaboradorIds = array_values(array_unique(array_filter(array_map('intval', (array)($filters['colaborador_ids'] ?? [])))));
        if (!empty($colaboradorIds)) {
            $holders = [];
            foreach (array_values($colaboradorIds) as $i => $id) {
                $k = 'col_' . $i;
                $holders[] = ':' . $k;
                $params[$k] = (int)$id;
            }
            $sql .= " AND col.id IN (" . implode(',', $holders) . ")";
        }
        if (!empty($filters['data_admissao_inicio'])) {
            $sql .= " AND col.data_admissao >= :data_admissao_inicio";
            $params['data_admissao_inicio'] = $filters['data_admissao_inicio'];
        }
        if (!empty($filters['data_admissao_fim'])) {
            $sql .= " AND col.data_admissao <= :data_admissao_fim";
            $params['data_admissao_fim'] = $filters['data_admissao_fim'];
        }
        $tempoMin = (int)($filters['tempo_meses_min'] ?? 0);
        $tempoMax = (int)($filters['tempo_meses_max'] ?? 0);
        if (($tempoMin > 0 || $tempoMax > 0) && Database::columnExists('colaboradores', 'data_admissao')) {
            $sql .= " AND col.data_admissao IS NOT NULL";
            if ($tempoMin > 0) {
                $sql .= " AND TIMESTAMPDIFF(MONTH, col.data_admissao, CURDATE()) >= :tempo_meses_min";
                $params['tempo_meses_min'] = $tempoMin;
            }
            if ($tempoMax > 0) {
                $sql .= " AND TIMESTAMPDIFF(MONTH, col.data_admissao, CURDATE()) <= :tempo_meses_max";
                $params['tempo_meses_max'] = $tempoMax;
            }
        }
        if (!empty($filters['status_atual'])) {
            $sql .= " AND COALESCE(NULLIF(TRIM(col.status_atual), ''), 'ativo') = :status_atual";
            $params['status_atual'] = trim((string)$filters['status_atual']);
        }
        if (!empty($filters['status_elegibilidade'])) {
            $sql .= " AND {$statusExpr} = :status_elegibilidade";
            $params['status_elegibilidade'] = trim((string)$filters['status_elegibilidade']);
        }
        if (!empty($filters['lideranca']) && Database::columnExists('colaboradores', 'lider')) {
            $v = strtolower(trim((string)$filters['lideranca']));
            if ($v === 'sim' || $v === 'nao') {
                $sql .= " AND col.lider = :lider";
                $params['lider'] = $v === 'sim' ? 'sim' : 'não';
            }
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= " AND (col.nome LIKE :q_nome OR col.email LIKE :q_email OR col.matricula LIKE :q_matricula OR col.cpf LIKE :q_cpf)";
            $like = '%' . $q . '%';
            $params['q_nome'] = $like;
            $params['q_email'] = $like;
            $params['q_matricula'] = $like;
            $params['q_cpf'] = $like;
        }
        $hist = strtolower(trim((string)($filters['historico'] ?? '')));
        if ($hist === 'nunca') {
            $sql .= " AND (th.total_conclusoes IS NULL OR th.total_conclusoes = 0)";
        } elseif ($hist === 'ja') {
            $sql .= " AND th.total_conclusoes > 0";
        } elseif ($hist === 'dias') {
            $dias = (int)($filters['historico_dias'] ?? 0);
            if ($dias > 0) {
                $sql .= " AND th.ultima_conclusao >= DATE_SUB(CURDATE(), INTERVAL :hist_dias DAY)";
                $params['hist_dias'] = $dias;
            }
        }
        if (Database::columnExists('colaboradores', 'ativo')) {
            $sql .= " AND col.ativo = 1";
        }
        $sql .= " ORDER BY col.nome";
        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $m)) {
            $used = array_fill_keys($m[1], true);
            $params = array_intersect_key($params, $used);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        $this->cachePut($cacheKey, $rows, 300);
        return $rows;
    }

    public function filterColaboradoresByCliente(int $clienteId, array $colaboradorIds): array
    {
        $this->ensureSchema();
        $colaboradorIds = array_values(array_unique(array_filter(array_map('intval', $colaboradorIds))));
        if ($clienteId <= 0 || empty($colaboradorIds)) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $holders = [];
        foreach ($colaboradorIds as $i => $id) {
            $k = 'c' . $i;
            $holders[] = ':' . $k;
            $params[$k] = (int)$id;
        }
        $sql = "SELECT id FROM colaboradores WHERE cliente_id = :cid AND id IN (" . implode(',', $holders) . ")";
        if (Database::columnExists('colaboradores', 'ativo')) {
            $sql .= " AND ativo = 1";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_values(array_map('intval', array_column($stmt->fetchAll() ?: [], 'id')));
    }

    private function participationByTraining(array $filters): array
    {
        $params = [];
        $sql = "SELECT
                    t.id AS treinamento_id,
                    t.nome AS treinamento_nome,
                    COALESCE(NULLIF(TRIM(t.tipo_treinamento), ''), 'Nao informado') AS tipo_treinamento,
                    COALESCE(NULLIF(TRIM(ta.instrutor), ''), 'Nao informado') AS instrutor,
                    COUNT(tp.id) AS total_inscritos,
                    SUM(CASE WHEN tp.presenca = 1 THEN 1 ELSE 0 END) AS total_presentes,
                    SUM(CASE WHEN tp.certificado_emitido = 1 THEN 1 ELSE 0 END) AS total_certificados
                FROM treinamentos t
                JOIN treinamentos_agenda ta ON ta.treinamento_id = t.id
                LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                JOIN departamentos d ON d.id = t.departamento_id
                LEFT JOIN colaboradores col ON col.id = tp.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                WHERE 1=1";
        $sql .= $this->applyDashboardFilters($filters, $params, true);
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'trpart');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " GROUP BY t.id, t.nome, tipo_treinamento, instrutor ORDER BY t.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $inscritos = (int)($row['total_inscritos'] ?? 0);
            $presentes = (int)($row['total_presentes'] ?? 0);
            $row['percentual_participacao'] = $inscritos > 0 ? round(($presentes / $inscritos) * 100, 2) : 0.0;
        }
        return $rows;
    }

    private function sectorTotals(array $filters): array
    {
        $params = [];
        $sql = "SELECT
                    s.id AS setor_id,
                    s.nome AS setor_nome,
                    COUNT(DISTINCT col_all.id) AS total_colaboradores_setor,
                    COUNT(DISTINCT CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN tp.colaborador_id END) AS total_treinados,
                    COALESCE(SUM(CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN t.carga_horaria ELSE 0 END), 0) AS total_horas_treinamento
                FROM setores s
                JOIN funcoes f_all ON f_all.setor_id = s.id
                JOIN colaboradores col_all ON col_all.funcao_id = f_all.id
                LEFT JOIN treinamento_participantes tp ON tp.colaborador_id = col_all.id
                LEFT JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id
                LEFT JOIN treinamentos t ON t.id = ta.treinamento_id
                LEFT JOIN departamentos d ON d.id = s.departamento_id
                WHERE 1=1";
        $sql .= $this->applyEmpresaDashboardFilter($filters, $params, ['d.cliente_id', 'col_all.cliente_id'], true);
        if (!empty($filters['setor_id'])) {
            $sql .= " AND s.id = :setor_id";
            $params['setor_id'] = (int)$filters['setor_id'];
        }
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND (ta.data IS NULL OR DATE(ta.data) >= :periodo_inicio)";
            $params['periodo_inicio'] = $filters['periodo_inicio'];
        }
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND (ta.data IS NULL OR DATE(ta.data) <= :periodo_fim)";
            $params['periodo_fim'] = $filters['periodo_fim'];
        }
        if (!empty($filters['tipo_treinamento'])) {
            $sql .= " AND (t.tipo_treinamento = :tipo_treinamento OR t.tipo_treinamento IS NULL)";
            $params['tipo_treinamento'] = trim((string)$filters['tipo_treinamento']);
        }
        if (!empty($filters['instrutor'])) {
            $sql .= " AND ta.instrutor LIKE :instrutor";
            $params['instrutor'] = '%' . trim((string)$filters['instrutor']) . '%';
        }
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'trset');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        if (Database::columnExists('colaboradores', 'ativo')) {
            $sql .= " AND col_all.ativo = 1";
        }
        $sql .= " GROUP BY s.id, s.nome ORDER BY s.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $totalColaboradores = (int)($row['total_colaboradores_setor'] ?? 0);
            $totalTreinados = (int)($row['total_treinados'] ?? 0);
            $horas = (float)($row['total_horas_treinamento'] ?? 0);
            $row['media_horas_por_colaborador'] = $totalColaboradores > 0 ? round($horas / $totalColaboradores, 2) : 0.0;
            $row['percentual_participacao'] = $totalColaboradores > 0 ? round(($totalTreinados / $totalColaboradores) * 100, 2) : 0.0;
        }
        return $rows;
    }

    private function periodAccumulators(array $filters): array
    {
        $params = [];
        $sql = "SELECT
                    DATE_FORMAT(ta.data, '%Y-%m') AS mensal,
                    CONCAT(YEAR(ta.data), '-T', QUARTER(ta.data)) AS trimestral,
                    YEAR(ta.data) AS anual,
                    COUNT(tp.id) AS total_inscritos,
                    SUM(CASE WHEN tp.presenca = 1 THEN 1 ELSE 0 END) AS total_presentes,
                    COALESCE(SUM(CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN t.carga_horaria ELSE 0 END), 0) AS total_horas
                FROM treinamentos_agenda ta
                JOIN treinamentos t ON t.id = ta.treinamento_id
                LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                JOIN departamentos d ON d.id = t.departamento_id
                LEFT JOIN colaboradores col ON col.id = tp.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                WHERE 1=1";
        $sql .= $this->applyDashboardFilters($filters, $params, true);
        $scope = $this->tenantCatalogInCondition('d.cliente_id', $params, 'tracc');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $sql .= " GROUP BY mensal, trimestral, anual ORDER BY mensal";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function applyDashboardFilters(array $filters, array &$params, bool $allowSetorFilter): string
    {
        $sql = '';
        if (!empty($filters['cliente_id'])) {
            $cid = (int)$filters['cliente_id'];
            $params['cliente_id_dep'] = $cid;
            $params['cliente_id_unidade'] = $cid;
            $params['cliente_id_col'] = $cid;
            $sql .= " AND d.cliente_id = :cliente_id_dep AND ta.unidade_id = :cliente_id_unidade AND (col.id IS NULL OR col.cliente_id = :cliente_id_col)";
        }
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND DATE(ta.data) >= :periodo_inicio";
            $params['periodo_inicio'] = $filters['periodo_inicio'];
        }
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND DATE(ta.data) <= :periodo_fim";
            $params['periodo_fim'] = $filters['periodo_fim'];
        }
        if ($allowSetorFilter && !empty($filters['setor_id'])) {
            $sql .= " AND s.id = :setor_id";
            $params['setor_id'] = (int)$filters['setor_id'];
        }
        if (!empty($filters['tipo_treinamento'])) {
            $sql .= " AND t.tipo_treinamento = :tipo_treinamento";
            $params['tipo_treinamento'] = trim((string)$filters['tipo_treinamento']);
        }
        if (!empty($filters['instrutor'])) {
            $sql .= " AND ta.instrutor LIKE :instrutor";
            $params['instrutor'] = '%' . trim((string)$filters['instrutor']) . '%';
        }
        return $sql;
    }

    private function applyEmpresaDashboardFilter(array $filters, array &$params, array $clienteColumns, bool $agendaNullable = false): string
    {
        if (empty($filters['cliente_id'])) {
            return '';
        }
        $cid = (int)$filters['cliente_id'];
        $clauses = [];
        foreach (array_values($clienteColumns) as $i => $col) {
            $k = 'dash_cliente_' . $i;
            $params[$k] = $cid;
            $clauses[] = $col . ' = :' . $k;
        }
        if ($agendaNullable) {
            $params['dash_cliente_ag'] = $cid;
            $clauses[] = '(ta.id IS NULL OR ta.unidade_id = :dash_cliente_ag)';
        }
        return ' AND ' . implode(' AND ', $clauses);
    }

    private function cacheGet(string $cacheKey): ?array
    {
        $stmt = $this->db->prepare("SELECT payload_json
            FROM treinamento_export_cache
            WHERE cache_key = :cache_key
              AND expires_at >= :now
            LIMIT 1");
        $stmt->execute([
            'cache_key' => $cacheKey,
            'now' => date('Y-m-d H:i:s'),
        ]);
        $payload = $stmt->fetchColumn();
        if ($payload === false) {
            return null;
        }
        $decoded = json_decode((string)$payload, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function cachePut(string $cacheKey, array $payload, int $ttlSeconds): void
    {
        $stmt = $this->db->prepare("INSERT INTO treinamento_export_cache (cache_key, payload_json, expires_at)
            VALUES (:cache_key, :payload_json, :expires_at)
            ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json), expires_at = VALUES(expires_at)");
        $stmt->execute([
            'cache_key' => $cacheKey,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlSeconds),
        ]);
    }
}

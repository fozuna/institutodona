<?php
namespace App\Models;

class TreinamentoAgendaModel extends BaseModel
{
    private bool $schemaEnsured = false;

    private function ensureSchema(): void
    {
        if ($this->schemaEnsured) {
            return;
        }
        TreinamentoSchema::ensure($this->db);
        $this->schemaEnsured = true;
    }

    public function create(array $data): int
    {
        $this->ensureSchema();
        if (!(new TreinamentoModel())->find((int)$data['treinamento_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare("INSERT INTO treinamentos_agenda
            (treinamento_id, data, unidade_id, responsavel_id, instrutor, local, observacoes)
            VALUES (:treinamento_id,:data,:unidade_id,:responsavel_id,:instrutor,:local,:observacoes)");
        $stmt->execute([
            'treinamento_id' => (int)$data['treinamento_id'],
            'data' => (string)$data['data'],
            'unidade_id' => (int)$data['unidade_id'],
            'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            'instrutor' => trim((string)($data['instrutor'] ?? '')),
            'local' => trim((string)($data['local'] ?? '')),
            'observacoes' => trim((string)($data['observacoes'] ?? '')),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listByTreinamento(int $treinamentoId): array
    {
        $this->ensureSchema();
        if (!(new TreinamentoModel())->find($treinamentoId)) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT
                ta.*,
                c.nome_empresa AS unidade_nome,
                u.nome AS responsavel_nome,
                (
                    SELECT COUNT(*)
                    FROM treinamento_participantes tp
                    WHERE tp.agenda_id = ta.id
                ) AS total_participantes,
                (
                    SELECT COUNT(*)
                    FROM treinamento_participantes tp
                    WHERE tp.agenda_id = ta.id AND tp.presenca = 1
                ) AS total_presentes
            FROM treinamentos_agenda ta
            JOIN clientes c ON c.id = ta.unidade_id
            LEFT JOIN usuarios u ON u.id = ta.responsavel_id
            WHERE ta.treinamento_id = :id
            ORDER BY ta.data DESC");
        $stmt->execute(['id' => $treinamentoId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $agendaId): ?array
    {
        $this->ensureSchema();
        $params = ['id' => $agendaId];
        $sql = "SELECT
                ta.*,
                t.nome AS treinamento_nome,
                t.objetivo,
                t.carga_horaria,
                c.nome_empresa AS unidade_nome,
                u.nome AS responsavel_nome
            FROM treinamentos_agenda ta
            JOIN treinamentos t ON t.id = ta.treinamento_id
            JOIN clientes c ON c.id = ta.unidade_id
            LEFT JOIN usuarios u ON u.id = ta.responsavel_id
            WHERE ta.id = :id";
        $scope = $this->tenantInCondition('ta.unidade_id', $params, 'tragfind');
        if ($scope !== '1=1') {
            $sql .= " AND {$scope}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function syncParticipants(int $agendaId, array $colaboradorIds): void
    {
        $this->ensureSchema();
        if (!$this->find($agendaId)) {
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO treinamento_participantes
            (agenda_id, colaborador_id, presenca, certificado_emitido)
            VALUES (:agenda_id,:colaborador_id,0,0)
            ON DUPLICATE KEY UPDATE agenda_id = agenda_id");
        foreach (array_values(array_unique(array_filter(array_map('intval', $colaboradorIds)))) as $colaboradorId) {
            $stmt->execute([
                'agenda_id' => $agendaId,
                'colaborador_id' => $colaboradorId,
            ]);
        }
    }

    public function participants(int $agendaId): array
    {
        $this->ensureSchema();
        if (!$this->find($agendaId)) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT
                tp.*,
                col.nome AS colaborador_nome,
                col.email AS colaborador_email,
                f.nome AS funcao_nome,
                s.nome AS setor_nome
            FROM treinamento_participantes tp
            JOIN colaboradores col ON col.id = tp.colaborador_id
            LEFT JOIN funcoes f ON f.id = col.funcao_id
            LEFT JOIN setores s ON s.id = f.setor_id
            WHERE tp.agenda_id = :agenda_id
            ORDER BY col.nome");
        $stmt->execute(['agenda_id' => $agendaId]);
        return $stmt->fetchAll() ?: [];
    }

    public function pendingParticipantsForTreinamento(int $treinamentoId, ?int $agendaId = null): array
    {
        $this->ensureSchema();
        $treinamento = new TreinamentoModel();
        if (!$treinamento->find($treinamentoId)) {
            return [];
        }
        $treinamento->refreshStatuses($treinamentoId);

        $params = ['treinamento_id' => $treinamentoId];
        $sql = "SELECT
                    tc.colaborador_id,
                    col.nome AS colaborador_nome,
                    col.email AS colaborador_email,
                    f.nome AS funcao_nome,
                    s.nome AS setor_nome
                FROM treinamento_colaboradores tc
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                WHERE tc.treinamento_id = :treinamento_id
                  AND tc.status = 'pendente'";
        if ($agendaId) {
            $sql .= " AND tc.colaborador_id NOT IN (
                SELECT tp.colaborador_id
                FROM treinamento_participantes tp
                WHERE tp.agenda_id = :agenda_id
            )";
            $params['agenda_id'] = $agendaId;
        }
        $sql .= " ORDER BY col.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function savePresence(int $agendaId, array $presencas, array $certificados): void
    {
        $this->ensureSchema();
        $agenda = $this->find($agendaId);
        if (!$agenda) {
            return;
        }
        $update = $this->db->prepare("UPDATE treinamento_participantes
            SET presenca = :presenca, certificado_emitido = :certificado_emitido
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $statusUpdate = $this->db->prepare("UPDATE treinamento_colaboradores
            SET status = :status
            WHERE treinamento_id = :treinamento_id AND colaborador_id = :colaborador_id");

        foreach ($this->participants($agendaId) as $participant) {
            $colaboradorId = (int)$participant['colaborador_id'];
            $presenca = !empty($presencas[$colaboradorId]) ? 1 : 0;
            $certificado = $presenca && !empty($certificados[$colaboradorId]) ? 1 : 0;
            $update->execute([
                'presenca' => $presenca,
                'certificado_emitido' => $certificado,
                'agenda_id' => $agendaId,
                'colaborador_id' => $colaboradorId,
            ]);
            $statusUpdate->execute([
                'status' => $certificado ? 'concluido' : 'pendente',
                'treinamento_id' => (int)$agenda['treinamento_id'],
                'colaborador_id' => $colaboradorId,
            ]);
        }

        (new TreinamentoModel())->refreshStatuses((int)$agenda['treinamento_id']);
    }

    public function issueCertificate(int $agendaId, int $colaboradorId): bool
    {
        $this->ensureSchema();
        $agenda = $this->find($agendaId);
        if (!$agenda) {
            return false;
        }
        $check = $this->db->prepare("SELECT presenca FROM treinamento_participantes
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $check->execute([
            'agenda_id' => $agendaId,
            'colaborador_id' => $colaboradorId,
        ]);
        $participant = $check->fetch();
        if (!$participant || (int)($participant['presenca'] ?? 0) !== 1) {
            return false;
        }
        $updateParticipant = $this->db->prepare("UPDATE treinamento_participantes
            SET certificado_emitido = 1
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $ok = $updateParticipant->execute([
            'agenda_id' => $agendaId,
            'colaborador_id' => $colaboradorId,
        ]);
        $this->db->prepare("UPDATE treinamento_colaboradores
            SET status = 'concluido'
            WHERE treinamento_id = :treinamento_id AND colaborador_id = :colaborador_id")
            ->execute([
                'treinamento_id' => (int)$agenda['treinamento_id'],
                'colaborador_id' => $colaboradorId,
            ]);
        (new TreinamentoModel())->refreshStatuses((int)$agenda['treinamento_id']);
        return $ok;
    }
}

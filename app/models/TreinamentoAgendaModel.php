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
            (treinamento_id, data, data_fim, unidade_id, responsavel_id, instrutor, local, observacoes)
            VALUES (:treinamento_id,:data,:data_fim,:unidade_id,:responsavel_id,:instrutor,:local,:observacoes)");
        $stmt->execute([
            'treinamento_id' => (int)$data['treinamento_id'],
            'data' => (string)$data['data'],
            'data_fim' => !empty($data['data_fim']) ? (string)$data['data_fim'] : null,
            'unidade_id' => (int)$data['unidade_id'],
            'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            'instrutor' => trim((string)($data['instrutor'] ?? '')),
            'local' => trim((string)($data['local'] ?? '')),
            'observacoes' => trim((string)($data['observacoes'] ?? '')),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $agendaId, array $data): bool
    {
        $this->ensureSchema();
        $agendaId = (int)$agendaId;
        $existing = $this->find($agendaId);
        if (!$existing) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE treinamentos_agenda
            SET data = :data,
                data_fim = :data_fim,
                unidade_id = :unidade_id,
                responsavel_id = :responsavel_id,
                instrutor = :instrutor,
                local = :local,
                observacoes = :observacoes
            WHERE id = :id");
        $stmt->execute([
            'id' => $agendaId,
            'data' => (string)$data['data'],
            'data_fim' => !empty($data['data_fim']) ? (string)$data['data_fim'] : null,
            'unidade_id' => (int)$data['unidade_id'],
            'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            'instrutor' => trim((string)($data['instrutor'] ?? '')),
            'local' => trim((string)($data['local'] ?? '')),
            'observacoes' => trim((string)($data['observacoes'] ?? '')),
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $agendaId): bool
    {
        $this->ensureSchema();
        $agendaId = (int)$agendaId;
        $existing = $this->find($agendaId);
        if (!$existing) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM treinamentos_agenda WHERE id = :id');
        $stmt->execute(['id' => $agendaId]);
        return $stmt->rowCount() > 0;
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
                ,
                (
                    SELECT COUNT(*)
                    FROM treinamento_participantes tp
                    WHERE tp.agenda_id = ta.id AND tp.certificado_emitido = 1
                ) AS total_certificados
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
                col.matricula,
                col.cpf,
                col.status_atual,
                f.nome AS funcao_nome,
                s.nome AS setor_nome,
                d.nome AS departamento_nome
            FROM treinamento_participantes tp
            JOIN colaboradores col ON col.id = tp.colaborador_id
            LEFT JOIN funcoes f ON f.id = col.funcao_id
            LEFT JOIN setores s ON s.id = f.setor_id
            LEFT JOIN departamentos d ON d.id = s.departamento_id
            WHERE tp.agenda_id = :agenda_id
            ORDER BY col.nome");
        $stmt->execute(['agenda_id' => $agendaId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findParticipant(int $agendaId, int $colaboradorId): ?array
    {
        foreach ($this->participants($agendaId) as $participant) {
            if ((int)$participant['colaborador_id'] === $colaboradorId) {
                return $participant;
            }
        }
        return null;
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
                    tc.status,
                    col.nome AS colaborador_nome,
                    col.email AS colaborador_email,
                    f.nome AS funcao_nome,
                    s.nome AS setor_nome
                FROM treinamento_colaboradores tc
                JOIN colaboradores col ON col.id = tc.colaborador_id
                LEFT JOIN funcoes f ON f.id = col.funcao_id
                LEFT JOIN setores s ON s.id = f.setor_id
                WHERE tc.treinamento_id = :treinamento_id
                ";
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

    public function savePresence(int $agendaId, array $presencas, array $horasEntrada = [], array $horasSaida = [], array $observacoes = []): void
    {
        $this->ensureSchema();
        $agenda = $this->find($agendaId);
        if (!$agenda) {
            return;
        }
        $update = $this->db->prepare("UPDATE treinamento_participantes
            SET presenca = :presenca,
                presenca_confirmada_em = :presenca_confirmada_em,
                hora_entrada = :hora_entrada,
                hora_saida = :hora_saida,
                observacao = :observacao
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $statusUpdate = $this->db->prepare("UPDATE treinamento_colaboradores
            SET status = :status
            WHERE treinamento_id = :treinamento_id AND colaborador_id = :colaborador_id");

        foreach ($this->participants($agendaId) as $participant) {
            $colaboradorId = (int)$participant['colaborador_id'];
            $presenca = !empty($presencas[$colaboradorId]) ? 1 : 0;
            $entrada = $this->normalizeTimeValue($horasEntrada[$colaboradorId] ?? null);
            $saida = $this->normalizeTimeValue($horasSaida[$colaboradorId] ?? null);
            $observacao = trim((string)($observacoes[$colaboradorId] ?? ''));
            $presencaConfirmadaEm = $presenca ? date('Y-m-d H:i:s') : null;
            $update->execute([
                'presenca' => $presenca,
                'presenca_confirmada_em' => $presencaConfirmadaEm,
                'hora_entrada' => $presenca ? $entrada : null,
                'hora_saida' => $presenca ? $saida : null,
                'observacao' => $observacao !== '' ? $observacao : null,
                'agenda_id' => $agendaId,
                'colaborador_id' => $colaboradorId,
            ]);
            $statusUpdate->execute([
                'status' => ($presenca || !empty($participant['certificado_emitido'])) ? 'concluido' : 'pendente',
                'treinamento_id' => (int)$agenda['treinamento_id'],
                'colaborador_id' => $colaboradorId,
            ]);
            $this->audit('presenca_atualizada', [
                'treinamento_id' => (int)$agenda['treinamento_id'],
                'agenda_id' => $agendaId,
                'colaborador_id' => $colaboradorId,
                'presenca' => $presenca,
                'hora_entrada' => $presenca ? $entrada : null,
                'hora_saida' => $presenca ? $saida : null,
            ]);
        }

        (new TreinamentoModel())->refreshStatuses((int)$agenda['treinamento_id']);
    }

    public function issueCertificate(int $agendaId, int $colaboradorId, ?string $arquivo = null): ?array
    {
        $this->ensureSchema();
        $agenda = $this->find($agendaId);
        if (!$agenda) {
            return null;
        }
        $participant = $this->findParticipant($agendaId, $colaboradorId);
        if (!$participant) {
            return null;
        }

        $numero = trim((string)($participant['certificado_numero'] ?? ''));
        $codigo = trim((string)($participant['certificado_codigo'] ?? ''));
        if ($numero === '') {
            $numero = sprintf('CERT-%d-%d-%d', $agendaId, $colaboradorId, time());
        }
        if ($codigo === '') {
            $codigo = strtoupper(substr(hash('sha256', $agendaId . '|' . $colaboradorId . '|' . $numero), 0, 20));
        }

        $updateParticipant = $this->db->prepare("UPDATE treinamento_participantes
            SET certificado_emitido = 1,
                certificado_numero = :numero,
                certificado_codigo = :codigo,
                certificado_emitido_em = :emitido_em,
                certificado_arquivo = :arquivo
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $ok = $updateParticipant->execute([
            'numero' => $numero,
            'codigo' => $codigo,
            'emitido_em' => date('Y-m-d H:i:s'),
            'arquivo' => $arquivo,
            'agenda_id' => $agendaId,
            'colaborador_id' => $colaboradorId,
        ]);
        if (!$ok) {
            return null;
        }
        $this->db->prepare("UPDATE treinamento_colaboradores
            SET status = 'concluido'
            WHERE treinamento_id = :treinamento_id AND colaborador_id = :colaborador_id")
            ->execute([
                'treinamento_id' => (int)$agenda['treinamento_id'],
                'colaborador_id' => $colaboradorId,
            ]);
        $participantId = (int)($participant['id'] ?? 0);
        $this->audit('certificado_emitido', [
            'treinamento_id' => (int)$agenda['treinamento_id'],
            'agenda_id' => $agendaId,
            'participante_id' => $participantId,
            'colaborador_id' => $colaboradorId,
            'certificado_numero' => $numero,
            'certificado_codigo' => $codigo,
            'certificado_arquivo' => $arquivo,
        ]);
        (new TreinamentoModel())->refreshStatuses((int)$agenda['treinamento_id']);
        return $this->findParticipant($agendaId, $colaboradorId);
    }

    public function issueCertificateBatch(int $agendaId, array $colaboradorIds, callable $fileResolver): array
    {
        $issued = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $colaboradorIds)))) as $colaboradorId) {
            $arquivo = (string)$fileResolver($agendaId, $colaboradorId);
            $participant = $this->issueCertificate($agendaId, $colaboradorId, $arquivo);
            if ($participant) {
                $issued[] = $participant;
            }
        }
        return $issued;
    }

    public function updateCertificateFile(int $agendaId, int $colaboradorId, string $arquivo): void
    {
        $stmt = $this->db->prepare("UPDATE treinamento_participantes
            SET certificado_arquivo = :arquivo
            WHERE agenda_id = :agenda_id AND colaborador_id = :colaborador_id");
        $stmt->execute([
            'arquivo' => $arquivo,
            'agenda_id' => $agendaId,
            'colaborador_id' => $colaboradorId,
        ]);
    }

    private function normalizeTimeValue($value): ?string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $raw) === 1) {
            return $raw . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $raw) === 1) {
            return $raw;
        }
        return null;
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
            'created_by' => (int)($_SESSION['user']['id'] ?? 0) ?: null,
        ]);
    }
}

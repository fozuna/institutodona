<?php
namespace App\Models;

class AvaliacaoPublicaModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS avaliacoes_publicas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                avaliacao_id INT NULL,
                token CHAR(36) NOT NULL UNIQUE,
                nome VARCHAR(150) NULL,
                empresa VARCHAR(255) NULL,
                whatsapp VARCHAR(20) NULL,
                email VARCHAR(180) NULL,
                numero_funcionarios INT UNSIGNED NULL,
                numero_lideres INT UNSIGNED NULL,
                faturamento_anual BIGINT UNSIGNED NULL,
                tomador_decisao TINYINT(1) NULL,
                respostas_json TEXT NULL,
                nota_financeiro TINYINT NOT NULL DEFAULT 0,
                nota_mercado TINYINT NOT NULL DEFAULT 0,
                nota_pessoas TINYINT NOT NULL DEFAULT 0,
                nota_processo TINYINT NOT NULL DEFAULT 0,
                realidade_financeiro TINYINT NULL,
                realidade_mercado TINYINT NULL,
                realidade_pessoas TINYINT NULL,
                realidade_processo TINYINT NULL,
                status ENUM('pendente','iniciada','concluida') NOT NULL DEFAULT 'pendente',
                expiracao DATETIME NULL,
                data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                data_conclusao DATETIME NULL,
                UNIQUE KEY uq_avaliacao_publica_avaliacao (avaliacao_id),
                CONSTRAINT fk_avaliacoes_publicas_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("ALTER TABLE avaliacoes_publicas MODIFY avaliacao_id INT NULL");
            $this->db->exec("ALTER TABLE avaliacoes_publicas MODIFY expiracao DATETIME NULL");
            if (!\App\Database\Database::columnExists('avaliacoes_publicas', 'expiracao')) {
                $this->db->exec("ALTER TABLE avaliacoes_publicas ADD COLUMN expiracao DATETIME NULL AFTER status");
            }
            if (!\App\Database\Database::columnExists('avaliacoes_publicas', 'data_conclusao')) {
                $this->db->exec("ALTER TABLE avaliacoes_publicas ADD COLUMN data_conclusao DATETIME NULL AFTER data_criacao");
            }
        } catch (\PDOException $e) {
        }
    }

    public function findByAvaliacaoId(int $avaliacaoId): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM avaliacoes_publicas WHERE avaliacao_id = :avaliacao_id LIMIT 1');
        $stmt->execute(['avaliacao_id' => $avaliacaoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM avaliacoes_publicas WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createOrRefreshForAvaliacao(int $avaliacaoId, string $empresa = '', bool $forceNew = false): array
    {
        $this->ensureTable();
        $existing = $this->findByAvaliacaoId($avaliacaoId);
        if ($existing && !$forceNew && !$this->isExpired($existing) && ($existing['status'] ?? '') !== 'concluida') {
            return $existing;
        }
        $token = $this->generateToken();
        if ($existing) {
            $stmt = $this->db->prepare('UPDATE avaliacoes_publicas
                SET token = :token,
                    nome = NULL,
                    empresa = :empresa,
                    whatsapp = NULL,
                    email = NULL,
                    numero_funcionarios = NULL,
                    numero_lideres = NULL,
                    faturamento_anual = NULL,
                    tomador_decisao = NULL,
                    status = :status,
                    expiracao = NULL,
                    data_criacao = NOW(),
                    respostas_json = NULL,
                    nota_financeiro = 0,
                    nota_mercado = 0,
                    nota_pessoas = 0,
                    nota_processo = 0,
                    realidade_financeiro = NULL,
                    realidade_mercado = NULL,
                    realidade_pessoas = NULL,
                    realidade_processo = NULL,
                    data_conclusao = NULL
                WHERE id = :id');
            $stmt->execute([
                'token' => $token,
                'empresa' => $empresa !== '' ? $empresa : null,
                'status' => 'pendente',
                'id' => (int)$existing['id'],
            ]);
            $row = $this->findById((int)$existing['id']);
            return $row ?: [];
        }
        $stmt = $this->db->prepare('INSERT INTO avaliacoes_publicas (avaliacao_id, token, empresa, status, expiracao) VALUES (:avaliacao_id, :token, :empresa, :status, NULL)');
        $stmt->execute([
            'avaliacao_id' => $avaliacaoId,
            'token' => $token,
            'empresa' => $empresa !== '' ? $empresa : null,
            'status' => 'pendente',
        ]);
        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: [];
    }

    public function createStandaloneLink(): array
    {
        $this->ensureTable();
        $token = $this->generateToken();
        $stmt = $this->db->prepare('INSERT INTO avaliacoes_publicas (avaliacao_id, token, empresa, status, expiracao) VALUES (NULL, :token, NULL, :status, NULL)');
        $stmt->execute([
            'token' => $token,
            'status' => 'pendente',
        ]);
        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: [];
    }

    public function startByToken(string $token, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE avaliacoes_publicas
            SET nome = :nome,
                empresa = :empresa,
                whatsapp = :whatsapp,
                email = :email,
                numero_funcionarios = :numero_funcionarios,
                numero_lideres = :numero_lideres,
                faturamento_anual = :faturamento_anual,
                tomador_decisao = :tomador_decisao,
                status = :status
            WHERE token = :token AND status <> :status_concluida AND (expiracao IS NULL OR expiracao >= NOW())');
        return $stmt->execute([
            'nome' => $data['nome'] ?? null,
            'empresa' => $data['empresa'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'numero_funcionarios' => isset($data['numero_funcionarios']) ? (int)$data['numero_funcionarios'] : null,
            'numero_lideres' => isset($data['numero_lideres']) ? (int)$data['numero_lideres'] : null,
            'faturamento_anual' => isset($data['faturamento_anual']) ? (int)$data['faturamento_anual'] : null,
            'tomador_decisao' => isset($data['tomador_decisao']) ? (int)$data['tomador_decisao'] : null,
            'status' => 'iniciada',
            'status_concluida' => 'concluida',
            'token' => $token,
        ]);
    }

    public function concludeByToken(string $token, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE avaliacoes_publicas
            SET respostas_json = :respostas_json,
                nota_financeiro = :nota_financeiro,
                nota_mercado = :nota_mercado,
                nota_pessoas = :nota_pessoas,
                nota_processo = :nota_processo,
                realidade_financeiro = :realidade_financeiro,
                realidade_mercado = :realidade_mercado,
                realidade_pessoas = :realidade_pessoas,
                realidade_processo = :realidade_processo,
                status = :status,
                data_conclusao = NOW()
            WHERE token = :token AND status = :status_iniciada AND (expiracao IS NULL OR expiracao >= NOW())');
        return $stmt->execute([
            'respostas_json' => $data['respostas_json'] ?? null,
            'nota_financeiro' => (int)($data['nota_financeiro'] ?? 0),
            'nota_mercado' => (int)($data['nota_mercado'] ?? 0),
            'nota_pessoas' => (int)($data['nota_pessoas'] ?? 0),
            'nota_processo' => (int)($data['nota_processo'] ?? 0),
            'realidade_financeiro' => isset($data['realidade_financeiro']) ? (int)$data['realidade_financeiro'] : null,
            'realidade_mercado' => isset($data['realidade_mercado']) ? (int)$data['realidade_mercado'] : null,
            'realidade_pessoas' => isset($data['realidade_pessoas']) ? (int)$data['realidade_pessoas'] : null,
            'realidade_processo' => isset($data['realidade_processo']) ? (int)$data['realidade_processo'] : null,
            'status' => 'concluida',
            'status_iniciada' => 'iniciada',
            'token' => $token,
        ]);
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM avaliacoes_publicas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function generateToken(): string
    {
        do {
            $token = $this->generateUuidV4();
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM avaliacoes_publicas WHERE token = :token');
            $stmt->execute(['token' => $token]);
            $exists = (int)$stmt->fetchColumn() > 0;
        } while ($exists);
        return $token;
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function isExpired(array $record): bool
    {
        $expiracao = (string)($record['expiracao'] ?? '');
        return $expiracao !== '' && strtotime($expiracao) < time();
    }
}

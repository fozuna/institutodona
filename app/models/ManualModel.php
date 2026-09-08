<?php
namespace App\Models;

use App\Core\Auth;

class ManualModel extends BaseModel
{
    private const SORT_COLUMNS = [
        'nome' => 'm.nome',
        'descricao' => 'COALESCE(m.descricao, \'\')',
        'empresa' => 'c.nome_empresa',
        'departamento' => 'd.nome',
        'data' => 'm.created_at',
    ];

    private const ALLOWED_TYPES = [
        'pdf',
        'doc', 'docx',
        'txt', 'rtf',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
    ];

    private const MIME_BY_EXT = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain'],
        'rtf' => ['application/rtf', 'text/rtf', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'svg' => ['image/svg+xml', 'text/xml', 'application/xml', 'application/octet-stream'],
    ];

    // Item 03: memoizacao por processo do "CREATE TABLE IF NOT EXISTS" - ver
    // ensureTable()/ensureLinkTable() para o motivo (DDL da commit implicito
    // no MySQL mesmo com a tabela ja existente, o que quebraria a transacao
    // de updateWithFilialLinks()).
    private static bool $tableEnsured = false;
    private static bool $linkTableEnsured = false;

    private function ensureTable(): void
    {
        // Item 03: memoizado por processo. CREATE TABLE IF NOT EXISTS e' DDL -
        // no MySQL/InnoDB, DDL sempre da commit implicito, mesmo quando a
        // tabela ja existe (a clausula IF NOT EXISTS nao evita o commit, so
        // evita o erro). Sem essa memoizacao, chamar update()/replaceFilialLinks()
        // (que reexecutam isso a cada chamada) de dentro de updateWithFilialLinks()
        // encerraria a transacao por baixo dos panos antes do commit()/rollBack()
        // explicito.
        if (self::$tableEnsured) {
            return;
        }
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS manuais (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                departamento_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                descricao VARCHAR(500) NULL,
                arquivo VARCHAR(255) NOT NULL,
                tipo_arquivo VARCHAR(10) NOT NULL,
                tamanho INT UNSIGNED NOT NULL DEFAULT 0,
                usuario_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_manuais_empresa (empresa_id),
                INDEX idx_manuais_departamento (departamento_id),
                INDEX idx_manuais_nome (nome),
                CONSTRAINT fk_manuais_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
                CONSTRAINT fk_manuais_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
        self::$tableEnsured = true;
    }

    private function ensureLinkTable(): void
    {
        $this->ensureTable();
        if (self::$linkTableEnsured) {
            return;
        }
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS manual_filial_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                manual_id INT NOT NULL,
                filial_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_manual_filial (manual_id, filial_id),
                INDEX idx_mfl_manual (manual_id),
                INDEX idx_mfl_filial (filial_id),
                CONSTRAINT fk_mfl_manual FOREIGN KEY (manual_id) REFERENCES manuais(id) ON DELETE CASCADE,
                CONSTRAINT fk_mfl_filial FOREIGN KEY (filial_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
        self::$linkTableEnsured = true;
    }

    private function manualCatalogClienteId(int $manualId): ?int
    {
        $manual = $this->findAny($manualId);
        if (!$manual) {
            return null;
        }
        $empresaId = (int)($manual['empresa_id'] ?? 0);
        $catalogClienteId = (int)($this->resolveCatalogClienteId($empresaId) ?? 0);
        return $catalogClienteId > 0 ? $catalogClienteId : null;
    }

    public function list(array $filters = []): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('m.empresa_id', $params, 'ml');
        $sql = "SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                       c.nome_empresa AS empresa_nome, d.nome AS departamento_nome, u.nome AS usuario_nome
                FROM manuais m
                JOIN clientes c ON c.id = m.empresa_id
                JOIN departamentos d ON d.id = m.departamento_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE $scope";

        if (!empty($filters['empresa_id'])) {
            $sql .= ' AND m.empresa_id = :empresa_id';
            $params['empresa_id'] = (int)$filters['empresa_id'];
        }
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND m.departamento_id = :departamento_id';
            $params['departamento_id'] = (int)$filters['departamento_id'];
        }
        if (!empty($filters['nome'])) {
            $sql .= ' AND m.nome LIKE :nome';
            $params['nome'] = '%' . trim((string)$filters['nome']) . '%';
        }

        $sql .= ' ORDER BY ' . self::orderByClause(
            self::normalizeSortColumn($filters['sort_col'] ?? null),
            self::normalizeSortDirection($filters['sort_dir'] ?? null)
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listBiblioteca(int $empresaId, int $matrizId, array $filters = []): array
    {
        $this->ensureLinkTable();
        $empresaId = (int)$empresaId;
        $matrizId = (int)$matrizId;
        if ($empresaId <= 0) {
            return $this->list($filters);
        }
        $params = [
            'empresa_id' => $empresaId,
            'empresa_id2' => $empresaId,
            'matriz_id' => $matrizId,
            'matriz_id2' => $matrizId,
        ];
        $sql = "SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                       c.nome_empresa AS empresa_nome, d.nome AS departamento_nome, u.nome AS usuario_nome,
                       CASE WHEN m.empresa_id = :matriz_id THEN 1 ELSE 0 END AS is_linked_from_matriz
                FROM manuais m
                JOIN clientes c ON c.id = m.empresa_id
                JOIN departamentos d ON d.id = m.departamento_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE (m.empresa_id = :empresa_id OR (m.empresa_id = :matriz_id2 AND EXISTS (
                    SELECT 1 FROM manual_filial_links l WHERE l.manual_id = m.id AND l.filial_id = :empresa_id2
                )))";

        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND m.departamento_id = :departamento_id';
            $params['departamento_id'] = (int)$filters['departamento_id'];
        }
        if (!empty($filters['nome'])) {
            $sql .= ' AND m.nome LIKE :nome';
            $params['nome'] = '%' . trim((string)$filters['nome']) . '%';
        }

        $sql .= ' ORDER BY ' . self::orderByClause(
            self::normalizeSortColumn($filters['sort_col'] ?? null),
            self::normalizeSortDirection($filters['sort_dir'] ?? null)
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('m.empresa_id', $params, 'mf');
        $stmt = $this->db->prepare("SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                                           c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                                    FROM manuais m
                                    JOIN clientes c ON c.id = m.empresa_id
                                    JOIN departamentos d ON d.id = m.departamento_id
                                    WHERE m.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
        // Item 03: existencia + escopo de tenant sao decididos AQUI (via find(),
        // que ja aplica tenantInCondition()), nunca mais por rowCount() do
        // UPDATE abaixo. Um UPDATE que casa a linha mas nao muda nenhuma coluna
        // (ex.: editar so os vinculos de filial, sem tocar em Nome/Descricao/
        // Empresa/Departamento/arquivo) tem rowCount() = 0 no MySQL mesmo sendo
        // uma operacao inteiramente valida - era essa a causa do falso erro
        // "Falha ao atualizar manual." ao alterar somente as filiais.
        if ($this->find($id) === null) {
            return false;
        }
        $params = [
            'id' => $id,
            'empresa_id' => (int)$this->normalizeScopedClienteId(isset($data['empresa_id']) ? (int)$data['empresa_id'] : null),
            'departamento_id' => (int)($data['departamento_id'] ?? 0),
            'nome' => trim((string)($data['nome'] ?? '')),
            'descricao' => array_key_exists('descricao', $data) ? ($data['descricao'] !== null ? trim((string)$data['descricao']) : null) : null,
            'arquivo' => (string)($data['arquivo'] ?? ''),
            'tipo_arquivo' => (string)($data['tipo_arquivo'] ?? ''),
            'tamanho' => (int)($data['tamanho'] ?? 0),
        ];
        if ($params['empresa_id'] <= 0 || !$this->canAccessClienteId($params['empresa_id'])) {
            return false;
        }
        if (!$this->departamentoBelongsToCatalogCliente($params['departamento_id'], $params['empresa_id'])) {
            return false;
        }
        $scope = $this->tenantInCondition('empresa_id', $params, 'mu');
        $stmt = $this->db->prepare("UPDATE manuais
            SET empresa_id = :empresa_id,
                departamento_id = :departamento_id,
                nome = :nome,
                descricao = :descricao,
                arquivo = :arquivo,
                tipo_arquivo = :tipo_arquivo,
                tamanho = :tamanho
            WHERE id = :id AND $scope");
        // execute() (nao rowCount()) e' a fonte de verdade de sucesso a partir
        // daqui: a existencia+tenant ja foram confirmados acima, e o PDO deste
        // projeto usa ERRMODE_EXCEPTION - um erro real de SQL propaga como
        // excecao (mesmo comportamento de antes), nunca vira um "false" silencioso.
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('empresa_id', $params, 'mdel');
        $stmt = $this->db->prepare("DELETE FROM manuais WHERE id = :id AND $scope");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function findAny(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.arquivo, m.tipo_arquivo, m.tamanho, m.usuario_id, m.created_at,
                                           c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                                    FROM manuais m
                                    JOIN clientes c ON c.id = m.empresa_id
                                    JOIN departamentos d ON d.id = m.departamento_id
                                    WHERE m.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function linkedFilialIds(int $manualId): array
    {
        $this->ensureLinkTable();
        $manualId = (int)$manualId;
        if ($manualId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare('SELECT filial_id FROM manual_filial_links WHERE manual_id = :mid ORDER BY filial_id');
        $stmt->execute(['mid' => $manualId]);
        return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll() ?: [], 'filial_id'))));
    }

    public function replaceFilialLinks(int $manualId, array $filialIds): void
    {
        $this->ensureLinkTable();
        $manualId = (int)$manualId;
        if ($manualId <= 0) {
            return;
        }
        $catalogClienteId = $this->manualCatalogClienteId($manualId);
        if ($catalogClienteId === null || $catalogClienteId <= 0) {
            return;
        }
        $this->db->prepare('DELETE FROM manual_filial_links WHERE manual_id = :mid')->execute(['mid' => $manualId]);
        $filialIds = array_values(array_unique(array_filter(array_map('intval', $filialIds))));
        if (empty($filialIds)) {
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO manual_filial_links (manual_id, filial_id) VALUES (:mid, :fid)');
        foreach ($filialIds as $fid) {
            if (!$this->canAccessClienteId($fid) && !Auth::isInstituto()) {
                continue;
            }
            $filialCatalogId = (int)($this->resolveCatalogClienteId($fid) ?? 0);
            if ($filialCatalogId !== $catalogClienteId) {
                continue;
            }
            $stmt->execute(['mid' => $manualId, 'fid' => $fid]);
        }
    }


    /**
     * Item 03: coordena update() + replaceFilialLinks() numa unica transacao,
     * para que a edicao do Manual e a troca de vinculos com filiais sejam
     * atomicas - se qualquer etapa falhar, nenhuma das duas fica aplicada
     * parcialmente. Reaproveita os dois metodos existentes sem duplicar a
     * logica de cada um; nenhuma mudanca de comportamento para quem ainda
     * chama update()/replaceFilialLinks() separadamente (ex.: store(), que
     * cria o vinculo junto com o INSERT e nao sofre do mesmo problema de
     * rowCount()).
     */
    public function updateWithFilialLinks(int $id, array $data, array $filialIds): bool
    {
        $this->ensureTable();
        $this->ensureLinkTable();
        try {
            $this->db->beginTransaction();
            if (!$this->update($id, $data)) {
                $this->db->rollBack();
                return false;
            }
            $this->replaceFilialLinks($id, $filialIds);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            try {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Throwable $e2) {
            }
            return false;
        }
    }

    public function isLinkedToFilial(int $manualId, int $filialId): bool
    {
        $this->ensureLinkTable();
        $manualId = (int)$manualId;
        $filialId = (int)$filialId;
        if ($manualId <= 0 || $filialId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT 1 FROM manual_filial_links WHERE manual_id = :mid AND filial_id = :fid LIMIT 1');
        $stmt->execute(['mid' => $manualId, 'fid' => $filialId]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $empresaId = (int)$this->normalizeScopedClienteId(isset($data['empresa_id']) ? (int)$data['empresa_id'] : null);
        if ($empresaId <= 0 || !$this->canAccessClienteId($empresaId)) {
            return 0;
        }
        $departamentoId = (int)($data['departamento_id'] ?? 0);
        if (!$this->departamentoBelongsToCatalogCliente($departamentoId, $empresaId)) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO manuais (empresa_id, departamento_id, nome, descricao, arquivo, tipo_arquivo, tamanho, usuario_id) VALUES (:empresa_id, :departamento_id, :nome, :descricao, :arquivo, :tipo_arquivo, :tamanho, :usuario_id)');
        $stmt->execute([
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => trim((string)$data['nome']),
            'descricao' => $data['descricao'] !== null ? trim((string)$data['descricao']) : null,
            'arquivo' => (string)$data['arquivo'],
            'tipo_arquivo' => (string)$data['tipo_arquivo'],
            'tamanho' => (int)$data['tamanho'],
            'usuario_id' => isset($data['usuario_id']) ? (int)$data['usuario_id'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public static function allowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    public static function extensionFromUpload(string $clientFilename): ?string
    {
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_TYPES, true) ? $ext : null;
    }

    public static function categoryFromExtension(string $ext): string
    {
        $ext = strtolower($ext);
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'], true)) {
            return 'imagens';
        }
        if (in_array($ext, ['xls', 'xlsx'], true)) {
            return 'planilhas';
        }
        if (in_array($ext, ['ppt', 'pptx'], true)) {
            return 'apresentacoes';
        }
        if (in_array($ext, ['txt', 'rtf'], true)) {
            return 'texto';
        }
        return 'documentos';
    }

    public static function validateUpload(string $clientFilename, int $sizeBytes, string $detectedMime, int $maxBytes = 52428800): array
    {
        $ext = self::extensionFromUpload($clientFilename);
        if ($ext === null) {
            return ['ok' => false, 'error' => 'ext_invalid', 'message' => 'Tipo de arquivo inválido.'];
        }
        if ($sizeBytes <= 0) {
            return ['ok' => false, 'error' => 'size_invalid', 'message' => 'Arquivo inválido.'];
        }
        if ($sizeBytes > $maxBytes) {
            return ['ok' => false, 'error' => 'size_exceeded', 'message' => 'Arquivo excede o limite de 50MB.'];
        }
        $mime = strtolower(trim($detectedMime));
        $allowed = self::MIME_BY_EXT[$ext] ?? [];
        if (!in_array($mime, $allowed, true)) {
            return ['ok' => false, 'error' => 'mime_invalid', 'message' => 'Tipo MIME inválido para o arquivo selecionado.'];
        }
        return [
            'ok' => true,
            'ext' => $ext,
            'mime' => $mime,
            'category' => self::categoryFromExtension($ext),
        ];
    }

    public static function storageDirFor(int $empresaId, int $departamentoId, ?string $category = null): string
    {
        $base = dirname(__DIR__, 2) . '/storage/manuais/' . $empresaId . '/' . $departamentoId;
        if ($category !== null && $category !== '') {
            $safe = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower($category)) ?: 'documentos';
            return $base . '/' . $safe;
        }
        return $base;
    }

    public static function sortableColumns(): array
    {
        return array_keys(self::SORT_COLUMNS);
    }

    public static function normalizeSortColumn(?string $value): string
    {
        $column = trim((string)$value);
        return array_key_exists($column, self::SORT_COLUMNS) ? $column : 'data';
    }

    public static function normalizeSortDirection(?string $value): string
    {
        return strtolower(trim((string)$value)) === 'asc' ? 'asc' : 'desc';
    }

    public function portalCount(array $empresaIds, array $filters = []): int
    {
        $this->ensureTable();
        if (empty($empresaIds)) {
            return 0;
        }
        $params = [];
        $sql = 'SELECT COUNT(*) FROM manuais m WHERE ' . $this->companyScopeClause($empresaIds, $params, 'mpc');
        $sql .= $this->portalFilterClause($filters, $params, 'mpcf');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function portalList(array $empresaIds, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $this->ensureTable();
        if (empty($empresaIds)) {
            return [];
        }
        $params = [];
        $sql = "SELECT m.id, m.empresa_id, m.departamento_id, m.nome, m.descricao, m.tipo_arquivo, m.tamanho, m.created_at,
                       c.nome_empresa AS empresa_nome, d.nome AS departamento_nome
                FROM manuais m
                JOIN clientes c ON c.id = m.empresa_id
                JOIN departamentos d ON d.id = m.departamento_id
                WHERE " . $this->companyScopeClause($empresaIds, $params, 'mpl');
        $sql .= $this->portalFilterClause($filters, $params, 'mplf');
        $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $perPage), \PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, ($page - 1) * $perPage), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function portalAllowsManual(int $manualId, array $empresaIds, array $filters = []): bool
    {
        $this->ensureTable();
        $manualId = (int)$manualId;
        if ($manualId <= 0 || empty($empresaIds)) {
            return false;
        }
        $params = ['id' => $manualId];
        $sql = 'SELECT COUNT(*) FROM manuais m WHERE m.id = :id AND ' . $this->companyScopeClause($empresaIds, $params, 'pmx');
        $sql .= $this->portalFilterClause($filters, $params, 'pmxf');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function companyScopeClause(array $empresaIds, array &$params, string $prefix): string
    {
        $placeholders = [];
        foreach (array_values($empresaIds) as $i => $empresaId) {
            $key = $prefix . '_e' . $i;
            $params[$key] = (int)$empresaId;
            $placeholders[] = ':' . $key;
        }
        return 'm.empresa_id IN (' . implode(',', $placeholders) . ')';
    }

    private function portalFilterClause(array $filters, array &$params, string $prefix): string
    {
        $sql = '';
        if (!empty($filters['departamento_id'])) {
            $params[$prefix . '_dep'] = (int)$filters['departamento_id'];
            $sql .= ' AND m.departamento_id = :' . $prefix . '_dep';
        }
        if (!empty($filters['q'])) {
            $term = '%' . trim((string)$filters['q']) . '%';
            $params[$prefix . '_q_nome'] = $term;
            $params[$prefix . '_q_desc'] = $term;
            $sql .= ' AND (m.nome LIKE :' . $prefix . '_q_nome OR m.descricao LIKE :' . $prefix . '_q_desc)';
        }
        if (!empty($filters['data_de'])) {
            $params[$prefix . '_de'] = (string)$filters['data_de'] . ' 00:00:00';
            $sql .= ' AND m.created_at >= :' . $prefix . '_de';
        }
        if (!empty($filters['data_ate'])) {
            $params[$prefix . '_ate'] = (string)$filters['data_ate'] . ' 23:59:59';
            $sql .= ' AND m.created_at <= :' . $prefix . '_ate';
        }
        return $sql;
    }

    private static function orderByClause(string $sortCol, string $sortDir): string
    {
        $column = self::SORT_COLUMNS[self::normalizeSortColumn($sortCol)] ?? self::SORT_COLUMNS['data'];
        $direction = self::normalizeSortDirection($sortDir) === 'asc' ? 'ASC' : 'DESC';
        return $column . ' ' . $direction . ', m.id ' . $direction;
    }

    public static function canManage(): bool
    {
        $role = (string)(Auth::user()['tipo_acesso'] ?? '');
        return $role === 'instituto' || $role === 'cliente_admin';
    }

    public static function canDelete(): bool
    {
        $role = (string)(Auth::user()['tipo_acesso'] ?? '');
        return $role === 'instituto' || $role === 'cliente_admin';
    }
}

<?php
namespace App\Models;

use App\Core\I18n;
use App\Core\AuditLogger;
use App\Database\Database;

class IndicadorModel extends BaseModel
{
    public const META_TIPOS = [
        'minimo' => 'indicadores.option.tipo_meta.minimo',
        'maximo' => 'indicadores.option.tipo_meta.maximo',
    ];

    public const PERIODICIDADES = [
        'diaria' => 'indicadores.periodicidade.diaria',
        'semanal' => 'indicadores.periodicidade.semanal',
        'mensal' => 'indicadores.periodicidade.mensal',
        'bimestral' => 'indicadores.periodicidade.bimestral',
        'trimestral' => 'indicadores.periodicidade.trimestral',
        'semestral' => 'indicadores.periodicidade.semestral',
        'anual' => 'indicadores.periodicidade.anual',
    ];

    private ClienteModel $clientes;
    private DepartamentoModel $departamentos;
    private SetorModel $setores;
    private ColaboradorModel $colaboradores;
    private UnidadeMedidaModel $unidades;
    private IndicadorEventoModel $eventos;
    /** @var array<string,bool> */
    private array $schema = [];

    public function __construct()
    {
        parent::__construct();
        $this->clientes = new ClienteModel();
        $this->departamentos = new DepartamentoModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
        $this->unidades = new UnidadeMedidaModel();
        $this->eventos = new IndicadorEventoModel();
        $this->schema = $this->detectSchema();
    }

    public function periodicidades(): array
    {
        $labels = [];
        foreach (self::PERIODICIDADES as $value => $key) {
            $labels[$value] = I18n::t($key);
        }
        return $labels;
    }

    public function metaTipos(): array
    {
        $labels = [];
        foreach (self::META_TIPOS as $value => $key) {
            $labels[$value] = I18n::t($key);
        }
        return $labels;
    }

    public function defaultPayload(): array
    {
        return [
            'cliente_id' => 0,
            'cliente_nome' => '',
            'indicador' => '',
            'departamento_id' => 0,
            'setor_id' => 0,
            'responsavel_ids' => [],
            'periodicidade_tipo' => 'mensal',
            'data_inicial' => '',
            'data_final' => '',
            'valor' => '',
            'tipo_meta' => 'minimo',
            'unidade_medida_id' => 0,
            'valor_minimo' => '',
            'valor_maximo' => '',
        ];
    }

    public function sanitize(array $data): array
    {
        $payload = array_merge($this->defaultPayload(), $data);
        $payload['cliente_id'] = (int)$this->normalizeScopedClienteId((int)($payload['cliente_id'] ?? 0));
        $payload['indicador'] = trim((string)($payload['indicador'] ?? $payload['nome'] ?? ''));
        $payload['departamento_id'] = (int)($payload['departamento_id'] ?? 0);
        $payload['setor_id'] = (int)($payload['setor_id'] ?? 0);
        $payload['periodicidade_tipo'] = trim((string)($payload['periodicidade_tipo'] ?? 'mensal'));
        $payload['data_inicial'] = trim((string)($payload['data_inicial'] ?? ''));
        $payload['data_final'] = trim((string)($payload['data_final'] ?? ''));
        $payload['valor'] = $this->normalizeDecimal($payload['valor'] ?? null);
        $payload['tipo_meta'] = $this->normalizeMetaTipo($payload['tipo_meta'] ?? null);
        $payload['unidade_medida_id'] = (int)($payload['unidade_medida_id'] ?? 0);
        $payload['valor_minimo'] = $this->normalizeDecimal($payload['valor_minimo'] ?? null);
        $payload['valor_maximo'] = $this->normalizeDecimal($payload['valor_maximo'] ?? null);
        $payload['responsavel_ids'] = array_values(array_unique(array_filter(array_map('intval', (array)($payload['responsavel_ids'] ?? [])))));
        return $payload;
    }

    public function validate(array $input, ?int $ignoreId = null): array
    {
        $data = $this->sanitize($input);
        $errors = [];

        if ($data['cliente_id'] <= 0) {
            $errors['cliente_id'] = I18n::t('indicadores.validation.invalid_client');
        } elseif (!$this->clientes->findActive($data['cliente_id'])) {
            $errors['cliente_id'] = I18n::t('indicadores.validation.invalid_client');
        }

        if ($data['indicador'] === '') {
            $errors['indicador'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.indicador')]);
        } elseif (mb_strlen($data['indicador']) > 255) {
            $errors['indicador'] = I18n::t('indicadores.validation.max_length', ['field' => I18n::t('indicadores.label.indicador'), 'max' => 255]);
        } elseif ($data['cliente_id'] > 0 && $this->existsDuplicate($data['cliente_id'], $data['indicador'], $ignoreId)) {
            $errors['indicador'] = I18n::t('indicadores.validation.duplicate_indicator');
        }

        if ($data['departamento_id'] <= 0) {
            $errors['departamento_id'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.departamento')]);
        } elseif (!$this->departamentos->findActive($data['departamento_id'], $data['cliente_id'])) {
            $errors['departamento_id'] = I18n::t('indicadores.validation.invalid_department');
        }

        if ($data['setor_id'] <= 0) {
            $errors['setor_id'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.setor')]);
        } elseif (!$this->setores->findActive($data['setor_id'], $data['departamento_id'])) {
            $errors['setor_id'] = I18n::t('indicadores.validation.invalid_sector');
        }

        if (!isset(self::PERIODICIDADES[$data['periodicidade_tipo']])) {
            $errors['periodicidade_tipo'] = I18n::t('indicadores.validation.invalid_periodicidade');
        }

        if ($data['data_inicial'] === '') {
            $errors['data_inicial'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.data_inicial')]);
        }
        if ($data['data_final'] === '') {
            $errors['data_final'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.data_final')]);
        }
        if ($data['data_inicial'] !== '' && $data['data_final'] !== '' && strtotime($data['data_inicial']) > strtotime($data['data_final'])) {
            $errors['data_final'] = I18n::t('indicadores.validation.invalid_interval');
        }

        if ($data['valor'] === null) {
            $errors['valor'] = I18n::t('indicadores.validation.invalid_number', ['field' => I18n::t('indicadores.label.valor')]);
        } elseif ((float)$data['valor'] <= 0) {
            $errors['valor'] = I18n::t('indicadores.validation.positive_number', ['field' => I18n::t('indicadores.label.valor')]);
        }

        if (!isset(self::META_TIPOS[$data['tipo_meta']])) {
            $errors['tipo_meta'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.tipo_meta')]);
        }

        $unit = null;
        if ($data['unidade_medida_id'] <= 0) {
            $errors['unidade_medida_id'] = I18n::t('indicadores.validation.required', ['field' => I18n::t('indicadores.label.unidade_medida')]);
        } else {
            $unit = $this->unidades->findActive($data['unidade_medida_id']);
            if (!$unit) {
                $errors['unidade_medida_id'] = I18n::t('indicadores.validation.invalid_unit');
            }
        }

        if ($unit && $data['valor'] !== null) {
            $type = (string)($unit['tipo'] ?? '');
            if ($type === 'inteiro' && floor((float)$data['valor']) !== (float)$data['valor']) {
                $errors['valor'] = I18n::t('indicadores.validation.invalid_integer');
            }
            if ($type === 'percentual' && (float)$data['valor'] > 100) {
                $errors['valor'] = I18n::t('indicadores.validation.invalid_percentage');
            }
        }

        $minProvided = $data['valor_minimo'] !== null;
        $maxProvided = $data['valor_maximo'] !== null;
        if ($minProvided xor $maxProvided) {
            $errors['valor_minimo'] = I18n::t('indicadores.validation.invalid_limit_pair');
        } elseif ($minProvided && $maxProvided && (float)$data['valor_minimo'] >= (float)$data['valor_maximo']) {
            $errors['valor_minimo'] = I18n::t('indicadores.validation.invalid_limits');
        }

        if (!empty($data['responsavel_ids'])) {
            $responsaveis = $this->colaboradores->activeByIdsCliente($data['responsavel_ids'], $data['cliente_id']);
            if (count($responsaveis) !== count($data['responsavel_ids'])) {
                $errors['responsavel_ids'] = I18n::t('indicadores.validation.invalid_responsavel');
            }
        }

        return $errors;
    }

    public function all(): array
    {
        return $this->search([]);
    }

    public function search(array $filters): array
    {
        $params = [];
        $where = [];
        if ($this->schema['indicadores_deleted_at']) {
            $where[] = 'i.deleted_at IS NULL';
        }
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'inds');
        $where[] = $scope;

        $clienteId = (int)($filters['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $params['cid'] = $clienteId;
            $where[] = 'i.cliente_id = :cid';
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $nameColumn = $this->schema['indicadores_indicador'] ? 'i.indicador' : 'i.nome';
            $params['q'] = '%' . mb_strtolower($q) . '%';
            $where[] = 'LOWER(' . $nameColumn . ') LIKE :q';
        }

        $dateStart = trim((string)($filters['date_start'] ?? ''));
        $dateEnd = trim((string)($filters['date_end'] ?? ''));
        if ($dateStart !== '') {
            $params['date_start'] = $dateStart;
            $where[] = 'i.data_final >= :date_start';
        }
        if ($dateEnd !== '') {
            $params['date_end'] = $dateEnd;
            $where[] = 'i.data_inicial <= :date_end';
        }

        $orderBy = $this->schema['indicadores_indicador']
            ? 'i.indicador'
            : 'COALESCE(i.nome, i.id)';
        $sql = $this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY i.id
            ORDER BY c.nome_empresa, ' . $orderBy . ', i.id';
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return array_map(fn(array $row): array => $this->hydrate($row), $stmt->fetchAll());
        } catch (\PDOException $e) {
            AuditLogger::log('indicadores_search_error', 'indicadores', null, [
                'message' => $e->getMessage(),
                'fallback' => true,
            ]);
            return $this->legacySearch($filters);
        }
    }

    public function byCliente(int $clienteId): array
    {
        return $this->search(['cliente_id' => $clienteId]);
    }

    public function autocomplete(int $clienteId, string $q, int $limit = 10): array
    {
        $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return [];
        }
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $nameColumn = $this->schema['indicadores_indicador'] ? 'indicador' : 'nome';
        $params = [
            'cid' => $clienteId,
            'q' => mb_strtolower($q) . '%',
        ];
        $scope = $this->tenantInCondition('cliente_id', $params, 'inda');
        $where = ['cliente_id = :cid', $scope];
        if ($this->schema['indicadores_deleted_at']) {
            $where[] = 'deleted_at IS NULL';
        }
        $sql = 'SELECT DISTINCT ' . $nameColumn . ' AS nome
                FROM indicadores
                WHERE ' . implode(' AND ', $where) . '
                  AND LOWER(' . $nameColumn . ') LIKE :q
                ORDER BY ' . $nameColumn . '
                LIMIT ' . max(1, min(50, (int)$limit));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_values(array_filter(array_map(static fn($v): string => (string)$v, $stmt->fetchAll(\PDO::FETCH_COLUMN))));
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'indf');
        $where = ['i.id = :id', $scope];
        if ($this->schema['indicadores_deleted_at']) {
            $where[] = 'i.deleted_at IS NULL';
        }
        $sql = $this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY i.id
            LIMIT 1';
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (\PDOException $e) {
            AuditLogger::log('indicadores_find_error', 'indicadores', $id, [
                'message' => $e->getMessage(),
                'fallback' => true,
            ]);
            return $this->legacyFind($id);
        }
    }

    public function create(array $input, int $userId): int
    {
        $data = $this->sanitize($input);
        if ($data['cliente_id'] <= 0 || !$this->canAccessClienteId($data['cliente_id']) || !$this->clientes->findActive($data['cliente_id'])) {
            return 0;
        }
        $columns = [
            'cliente_id', 'indicador', 'nome', 'departamento_id', 'setor_id', 'periodicidade_tipo',
            'data_inicial', 'data_final', 'valor',
        ];
        $placeholders = [
            ':cliente_id', ':indicador', ':nome', ':departamento_id', ':setor_id', ':periodicidade_tipo',
            ':data_inicial', ':data_final', ':valor',
        ];
        if ($this->schema['indicadores_tipo_meta']) {
            $columns[] = 'tipo_meta';
            $placeholders[] = ':tipo_meta';
        }
        $columns = array_merge($columns, [
            'unidade_medida_id', 'valor_minimo', 'valor_maximo', 'created_at', 'updated_at', 'created_by', 'updated_by',
        ]);
        $placeholders = array_merge($placeholders, [
            ':unidade_medida_id', ':valor_minimo', ':valor_maximo', 'NOW()', 'NOW()', ':created_by', ':updated_by',
        ]);
        $stmt = $this->db->prepare(
            'INSERT INTO indicadores (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );
        $params = [
            'cliente_id' => $data['cliente_id'],
            'indicador' => $data['indicador'],
            'nome' => $data['indicador'],
            'departamento_id' => $data['departamento_id'],
            'setor_id' => $data['setor_id'],
            'periodicidade_tipo' => $data['periodicidade_tipo'],
            'data_inicial' => $data['data_inicial'],
            'data_final' => $data['data_final'],
            'valor' => $data['valor'],
            'unidade_medida_id' => $data['unidade_medida_id'],
            'valor_minimo' => $data['valor_minimo'],
            'valor_maximo' => $data['valor_maximo'],
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ];
        if ($this->schema['indicadores_tipo_meta']) {
            $params['tipo_meta'] = $data['tipo_meta'];
        }
        $stmt->execute($params);
        $id = (int)$this->db->lastInsertId();
        $this->syncResponsaveis($id, $data['responsavel_ids']);
        $this->eventos->syncForIndicator($this->find($id) ?: array_merge($data, ['id' => $id]), $userId);
        return $id;
    }

    public function update(int $id, array $input, int $userId): bool
    {
        $data = $this->sanitize($input);
        if ($data['cliente_id'] <= 0 || !$this->canAccessClienteId($data['cliente_id']) || !$this->clientes->findActive($data['cliente_id'])) {
            return false;
        }
        $params = [
            'id' => $id,
            'cliente_id' => $data['cliente_id'],
            'indicador' => $data['indicador'],
            'nome' => $data['indicador'],
            'departamento_id' => $data['departamento_id'],
            'setor_id' => $data['setor_id'],
            'periodicidade_tipo' => $data['periodicidade_tipo'],
            'data_inicial' => $data['data_inicial'],
            'data_final' => $data['data_final'],
            'valor' => $data['valor'],
            'unidade_medida_id' => $data['unidade_medida_id'],
            'valor_minimo' => $data['valor_minimo'],
            'valor_maximo' => $data['valor_maximo'],
            'updated_by' => $userId > 0 ? $userId : null,
        ];
        $setClauses = [
            'cliente_id = :cliente_id',
            'indicador = :indicador',
            'nome = :nome',
            'departamento_id = :departamento_id',
            'setor_id = :setor_id',
            'periodicidade_tipo = :periodicidade_tipo',
            'data_inicial = :data_inicial',
            'data_final = :data_final',
            'valor = :valor',
        ];
        if ($this->schema['indicadores_tipo_meta']) {
            $params['tipo_meta'] = $data['tipo_meta'];
            $setClauses[] = 'tipo_meta = :tipo_meta';
        }
        $setClauses = array_merge($setClauses, [
            'unidade_medida_id = :unidade_medida_id',
            'valor_minimo = :valor_minimo',
            'valor_maximo = :valor_maximo',
            'updated_at = NOW()',
            'updated_by = :updated_by',
        ]);
        $scope = $this->tenantInCondition('cliente_id', $params, 'indu');
        $stmt = $this->db->prepare(
            'UPDATE indicadores
             SET ' . implode(",
                 ", $setClauses) . '
             WHERE id = :id AND deleted_at IS NULL AND ' . $scope
        );
        $ok = $stmt->execute($params);
        if ($ok) {
            $this->syncResponsaveis($id, $data['responsavel_ids']);
            $this->eventos->syncForIndicator($this->find($id) ?: array_merge($data, ['id' => $id]), $userId);
        }
        return $ok;
    }

    public function updateValor(int $id, $valor, int $userId): bool
    {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }
        $normalized = $this->normalizeDecimal($valor);
        if ($normalized === null) {
            return false;
        }
        if ((float)$normalized <= 0) {
            return false;
        }
        $unit = $this->unidades->findActive((int)$item['unidade_medida_id']);
        if ($unit) {
            if (($unit['tipo'] ?? '') === 'inteiro' && floor((float)$normalized) !== (float)$normalized) {
                return false;
            }
            if (($unit['tipo'] ?? '') === 'percentual' && (float)$normalized > 100) {
                return false;
            }
        }
        $params = ['id' => $id, 'valor' => $normalized, 'updated_by' => $userId > 0 ? $userId : null];
        $scope = $this->tenantInCondition('cliente_id', $params, 'indv');
        $stmt = $this->db->prepare(
            'UPDATE indicadores
             SET valor = :valor,
                 updated_at = NOW(),
                 updated_by = :updated_by
             WHERE id = :id AND deleted_at IS NULL AND ' . $scope
        );
        $ok = $stmt->execute($params);
        if ($ok) {
            $this->eventos->syncForIndicator($this->find($id) ?: ['id' => $id], $userId);
        }
        return $ok;
    }

    public function softDelete(int $id, int $userId): bool
    {
        $params = [
            'id' => $id,
            'deleted_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ];
        $scope = $this->tenantInCondition('cliente_id', $params, 'indd');
        $stmt = $this->db->prepare(
            'UPDATE indicadores
             SET deleted_at = NOW(), deleted_by = :deleted_by, updated_at = NOW(), updated_by = :updated_by
             WHERE id = :id AND deleted_at IS NULL AND ' . $scope
        );
        $ok = $stmt->execute($params) && $stmt->rowCount() > 0;
        if ($ok) {
            $this->eventos->softDeleteByIndicator($id, $userId);
        }
        return $ok;
    }

    public function dashboardStats(int $clienteId): array
    {
        $items = $this->byCliente($clienteId);
        $stats = ['total' => count($items), 'low' => 0, 'high' => 0, 'normal' => 0, 'without_limits' => 0];
        foreach ($items as $item) {
            $status = (string)($item['control_status_key'] ?? 'no_limits');
            if ($status === 'low') {
                $stats['low']++;
            } elseif ($status === 'high') {
                $stats['high']++;
            } elseif ($status === 'normal') {
                $stats['normal']++;
            } else {
                $stats['without_limits']++;
            }
        }
        return $stats;
    }

    private function baseSelect(): string
    {
        $departJoin = $this->schema['indicadores_departamento_id'] ? 'LEFT JOIN departamentos d ON d.id = i.departamento_id' : '';
        $setorJoin = $this->schema['indicadores_setor_id'] ? 'LEFT JOIN setores s ON s.id = i.setor_id' : '';
        $unidadeJoin = ($this->schema['indicadores_unidade_medida_id'] && $this->schema['unidades_medida_table'])
            ? 'LEFT JOIN unidades_medida um ON um.id = i.unidade_medida_id'
            : '';
        $respJoin = $this->schema['indicador_responsavel_table']
            ? 'LEFT JOIN indicador_responsavel ir ON ir.indicador_id = i.id LEFT JOIN colaboradores col ON col.id = ir.colaborador_id'
            : '';
        $departSelect = $this->schema['indicadores_departamento_id'] ? 'd.nome AS departamento_nome' : "'' AS departamento_nome";
        $setorSelect = $this->schema['indicadores_setor_id'] ? 's.nome AS setor_nome' : "'' AS setor_nome";
        $unidadeNome = ($this->schema['indicadores_unidade_medida_id'] && $this->schema['unidades_medida_table']) ? 'um.nome' : "''";
        $unidadeSimbolo = ($this->schema['indicadores_unidade_medida_id'] && $this->schema['unidades_medida_table']) ? 'um.simbolo' : "''";
        $unidadeTipo = ($this->schema['indicadores_unidade_medida_id'] && $this->schema['unidades_medida_table']) ? 'um.tipo' : "''";
        $respConcat = $this->schema['indicador_responsavel_table']
            ? "GROUP_CONCAT(DISTINCT CONCAT(col.nome, ' <', COALESCE(col.email, ''), '>') ORDER BY col.nome SEPARATOR '||')"
            : "''";
        $respIds = $this->schema['indicador_responsavel_table']
            ? "GROUP_CONCAT(DISTINCT col.id ORDER BY col.nome SEPARATOR ',')"
            : "''";
        $metaTipoSelect = $this->schema['indicadores_tipo_meta']
            ? "COALESCE(i.tipo_meta, 'minimo')"
            : "'minimo'";
        return "SELECT
                i.*,
                c.nome_empresa AS cliente_nome,
                {$departSelect},
                {$setorSelect},
                {$metaTipoSelect} AS tipo_meta,
                {$unidadeNome} AS unidade_nome,
                {$unidadeSimbolo} AS unidade_simbolo,
                {$unidadeTipo} AS unidade_tipo,
                {$respConcat} AS responsaveis_concat,
                {$respIds} AS responsavel_ids_concat
            FROM indicadores i
            JOIN clientes c ON c.id = i.cliente_id
            {$departJoin}
            {$setorJoin}
            {$unidadeJoin}
            {$respJoin}";
    }

    private function hydrate(array $row): array
    {
        $row['nome'] = $row['indicador'] ?? $row['nome'] ?? '';
        $row['responsavel_ids'] = [];
        if (!empty($row['responsavel_ids_concat'])) {
            $row['responsavel_ids'] = array_values(array_filter(array_map('intval', explode(',', (string)$row['responsavel_ids_concat']))));
        }
        $row['responsaveis'] = [];
        if (!empty($row['responsaveis_concat'])) {
            foreach (explode('||', (string)$row['responsaveis_concat']) as $entry) {
                $entry = trim($entry);
                if ($entry !== '') {
                    $row['responsaveis'][] = $entry;
                }
            }
        }
        $control = $this->controlStatus($row);
        $row['control_status_key'] = $control['key'];
        $row['control_status_label'] = $control['label'];
        $row['control_status_class'] = $control['class'];
        return $row;
    }

    private function controlStatus(array $row): array
    {
        $valor = ($row['valor'] ?? null) !== null ? (float)$row['valor'] : null;
        $min = ($row['valor_minimo'] ?? null) !== null ? (float)$row['valor_minimo'] : null;
        $max = ($row['valor_maximo'] ?? null) !== null ? (float)$row['valor_maximo'] : null;
        if ($valor === null || $min === null || $max === null) {
            return ['key' => 'no_limits', 'label' => I18n::t('indicadores.control.no_limits'), 'class' => 'bg-gray-100 text-gray-700'];
        }
        if ($valor < $min) {
            return ['key' => 'low', 'label' => I18n::t('indicadores.control.low'), 'class' => 'bg-red-100 text-red-700'];
        }
        if ($valor > $max) {
            return ['key' => 'high', 'label' => I18n::t('indicadores.control.high'), 'class' => 'bg-amber-100 text-amber-700'];
        }
        return ['key' => 'normal', 'label' => I18n::t('indicadores.control.normal'), 'class' => 'bg-green-100 text-green-700'];
    }

    private function existsDuplicate(int $clienteId, string $indicador, ?int $ignoreId = null): bool
    {
        $nameColumn = $this->schema['indicadores_indicador'] ? 'indicador' : 'nome';
        $params = [
            'cid' => $clienteId,
            'nome' => mb_strtolower($indicador),
        ];
        $sql = 'SELECT id
                FROM indicadores
                WHERE cliente_id = :cid
                  AND LOWER(' . $nameColumn . ') = :nome';
        if ($this->schema['indicadores_deleted_at']) {
            $sql .= ' AND deleted_at IS NULL';
        }
        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    private function syncResponsaveis(int $indicadorId, array $responsavelIds): void
    {
        $del = $this->db->prepare('DELETE FROM indicador_responsavel WHERE indicador_id = :id');
        $del->execute(['id' => $indicadorId]);
        if (empty($responsavelIds)) {
            return;
        }
        $ins = $this->db->prepare(
            'INSERT INTO indicador_responsavel (indicador_id, colaborador_id, created_at)
             VALUES (:indicador_id, :colaborador_id, NOW())'
        );
        foreach ($responsavelIds as $colaboradorId) {
            $ins->execute([
                'indicador_id' => $indicadorId,
                'colaborador_id' => (int)$colaboradorId,
            ]);
        }
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $raw = trim($value);
            $raw = str_replace(['R$', ' '], '', $raw);
            if ($raw === '' || preg_match('/[^0-9,\\.\\-]/', $raw)) {
                return null;
            }
            if (substr_count($raw, ',') > 1) {
                return null;
            }
            if (str_contains($raw, ',')) {
                [$intPart, $decPart] = array_pad(explode(',', $raw, 2), 2, '');
                if ($decPart === '' || preg_match('/[^0-9]/', $decPart)) {
                    return null;
                }
                if ($intPart === '' || preg_match('/[^0-9\\.\\-]/', $intPart)) {
                    return null;
                }
                $intPart = str_replace('.', '', $intPart);
                $raw = $intPart . '.' . $decPart;
            } else {
                if (str_contains($raw, '.')) {
                    if (!preg_match('/^-?\d{1,3}(\.\d{3})+$/', $raw)) {
                        return null;
                    }
                    $raw = str_replace('.', '', $raw);
                }
            }
            $value = $raw;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return round((float)$value, 4);
    }

    /** @return array<string,bool> */
    private function detectSchema(): array
    {
        return [
            'indicadores_table' => Database::tableExists('indicadores'),
            'indicadores_deleted_at' => Database::columnExists('indicadores', 'deleted_at'),
            'indicadores_indicador' => Database::columnExists('indicadores', 'indicador'),
            'indicadores_tipo_meta' => Database::columnExists('indicadores', 'tipo_meta'),
            'indicadores_departamento_id' => Database::columnExists('indicadores', 'departamento_id'),
            'indicadores_setor_id' => Database::columnExists('indicadores', 'setor_id'),
            'indicadores_unidade_medida_id' => Database::columnExists('indicadores', 'unidade_medida_id'),
            'indicador_responsavel_table' => Database::tableExists('indicador_responsavel'),
            'unidades_medida_table' => Database::tableExists('unidades_medida'),
        ];
    }

    private function normalizeMetaTipo($value): string
    {
        $tipo = mb_strtolower(trim((string)$value));
        if ($tipo === '' || !isset(self::META_TIPOS[$tipo])) {
            return 'minimo';
        }
        return $tipo;
    }

    private function legacySearch(array $filters): array
    {
        if (!$this->schema['indicadores_table']) {
            return [];
        }
        $params = [];
        $where = [];
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'indsl');
        $where[] = $scope;
        $clienteId = (int)($filters['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $params['cid'] = $clienteId;
            $where[] = 'i.cliente_id = :cid';
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $nameColumn = $this->schema['indicadores_indicador'] ? 'i.indicador' : 'i.nome';
            $params['q'] = '%' . mb_strtolower($q) . '%';
            $where[] = 'LOWER(' . $nameColumn . ') LIKE :q';
        }
        $dateStart = trim((string)($filters['date_start'] ?? ''));
        $dateEnd = trim((string)($filters['date_end'] ?? ''));
        if ($dateStart !== '') {
            $params['date_start'] = $dateStart;
            $where[] = 'i.data_final >= :date_start';
        }
        if ($dateEnd !== '') {
            $params['date_end'] = $dateEnd;
            $where[] = 'i.data_inicial <= :date_end';
        }
        $sql = "SELECT
                    i.*,
                    c.nome_empresa AS cliente_nome,
                    '' AS departamento_nome,
                    '' AS setor_nome,
                    '' AS unidade_nome,
                    '' AS unidade_simbolo,
                    '' AS unidade_tipo,
                    '' AS responsaveis_concat,
                    '' AS responsavel_ids_concat
                FROM indicadores i
                JOIN clientes c ON c.id = i.cliente_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.nome_empresa, i.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->hydrate($row), $stmt->fetchAll());
    }

    private function legacyFind(int $id): ?array
    {
        if (!$this->schema['indicadores_table']) {
            return null;
        }
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'indfl');
        $sql = "SELECT
                    i.*,
                    c.nome_empresa AS cliente_nome,
                    '' AS departamento_nome,
                    '' AS setor_nome,
                    '' AS unidade_nome,
                    '' AS unidade_simbolo,
                    '' AS unidade_tipo,
                    '' AS responsaveis_concat,
                    '' AS responsavel_ids_concat
                FROM indicadores i
                JOIN clientes c ON c.id = i.cliente_id
                WHERE i.id = :id AND {$scope}
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }
}

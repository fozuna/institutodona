<?php
namespace App\Models;

use App\Core\I18n;

class IndicadorModel extends BaseModel
{
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

    public function __construct()
    {
        parent::__construct();
        $this->clientes = new ClienteModel();
        $this->departamentos = new DepartamentoModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
        $this->unidades = new UnidadeMedidaModel();
        $this->eventos = new IndicadorEventoModel();
    }

    public function periodicidades(): array
    {
        $labels = [];
        foreach (self::PERIODICIDADES as $value => $key) {
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
            if ($type === 'percentual' && ((float)$data['valor'] < 0 || (float)$data['valor'] > 100)) {
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
        $where = ['i.deleted_at IS NULL'];
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'inds');
        $where[] = $scope;

        $clienteId = (int)($filters['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $params['cid'] = $clienteId;
            $where[] = 'i.cliente_id = :cid';
        }

        $sql = $this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY i.id
            ORDER BY c.nome_empresa, i.indicador, i.data_inicial';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->hydrate($row), $stmt->fetchAll());
    }

    public function byCliente(int $clienteId): array
    {
        return $this->search(['cliente_id' => $clienteId]);
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('i.cliente_id', $params, 'indf');
        $sql = $this->baseSelect() . '
            WHERE i.id = :id AND i.deleted_at IS NULL AND ' . $scope . '
            GROUP BY i.id
            LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $input, int $userId): int
    {
        $data = $this->sanitize($input);
        $stmt = $this->db->prepare(
            'INSERT INTO indicadores (
                cliente_id, indicador, nome, departamento_id, setor_id, periodicidade_tipo,
                data_inicial, data_final, valor, unidade_medida_id, valor_minimo, valor_maximo,
                created_at, updated_at, created_by, updated_by
            ) VALUES (
                :cliente_id, :indicador, :nome, :departamento_id, :setor_id, :periodicidade_tipo,
                :data_inicial, :data_final, :valor, :unidade_medida_id, :valor_minimo, :valor_maximo,
                NOW(), NOW(), :created_by, :updated_by
            )'
        );
        $stmt->execute([
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
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->syncResponsaveis($id, $data['responsavel_ids']);
        $this->eventos->syncForIndicator($this->find($id) ?: array_merge($data, ['id' => $id]), $userId);
        return $id;
    }

    public function update(int $id, array $input, int $userId): bool
    {
        $data = $this->sanitize($input);
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
        $scope = $this->tenantInCondition('cliente_id', $params, 'indu');
        $stmt = $this->db->prepare(
            'UPDATE indicadores
             SET cliente_id = :cliente_id,
                 indicador = :indicador,
                 nome = :nome,
                 departamento_id = :departamento_id,
                 setor_id = :setor_id,
                 periodicidade_tipo = :periodicidade_tipo,
                 data_inicial = :data_inicial,
                 data_final = :data_final,
                 valor = :valor,
                 unidade_medida_id = :unidade_medida_id,
                 valor_minimo = :valor_minimo,
                 valor_maximo = :valor_maximo,
                 updated_at = NOW(),
                 updated_by = :updated_by
             WHERE id = :id AND deleted_at IS NULL AND ' . $scope
        );
        $ok = $stmt->execute($params) && $stmt->rowCount() > 0;
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
        $unit = $this->unidades->findActive((int)$item['unidade_medida_id']);
        if ($unit) {
            if (($unit['tipo'] ?? '') === 'inteiro' && floor((float)$normalized) !== (float)$normalized) {
                return false;
            }
            if (($unit['tipo'] ?? '') === 'percentual' && ((float)$normalized < 0 || (float)$normalized > 100)) {
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
        $ok = $stmt->execute($params) && $stmt->rowCount() > 0;
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
        return "SELECT
                i.*,
                c.nome_empresa AS cliente_nome,
                d.nome AS departamento_nome,
                s.nome AS setor_nome,
                um.nome AS unidade_nome,
                um.simbolo AS unidade_simbolo,
                um.tipo AS unidade_tipo,
                GROUP_CONCAT(DISTINCT CONCAT(col.nome, ' <', COALESCE(col.email, ''), '>') ORDER BY col.nome SEPARATOR '||') AS responsaveis_concat,
                GROUP_CONCAT(DISTINCT col.id ORDER BY col.nome SEPARATOR ',') AS responsavel_ids_concat
            FROM indicadores i
            JOIN clientes c ON c.id = i.cliente_id
            LEFT JOIN departamentos d ON d.id = i.departamento_id
            LEFT JOIN setores s ON s.id = i.setor_id
            LEFT JOIN unidades_medida um ON um.id = i.unidade_medida_id
            LEFT JOIN indicador_responsavel ir ON ir.indicador_id = i.id
            LEFT JOIN colaboradores col ON col.id = ir.colaborador_id";
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
        $valor = $row['valor'] !== null ? (float)$row['valor'] : null;
        $min = $row['valor_minimo'] !== null ? (float)$row['valor_minimo'] : null;
        $max = $row['valor_maximo'] !== null ? (float)$row['valor_maximo'] : null;
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
        $params = [
            'cid' => $clienteId,
            'nome' => mb_strtolower($indicador),
        ];
        $sql = 'SELECT id
                FROM indicadores
                WHERE cliente_id = :cid
                  AND deleted_at IS NULL
                  AND LOWER(indicador) = :nome';
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
            $value = trim($value);
            $value = str_replace(['R$', ' '], '', $value);
            if (substr_count($value, ',') === 1 && substr_count($value, '.') >= 1) {
                $value = str_replace('.', '', $value);
            }
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value)) {
            return null;
        }
        return round((float)$value, 4);
    }
}

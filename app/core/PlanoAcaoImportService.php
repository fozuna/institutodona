<?php
namespace App\Core;

use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;
use App\Models\ClienteModel;

class PlanoAcaoImportService
{
    private PlanoAcaoTaskModel $tasks;
    private ClienteModel $clientes;

    public function __construct(?PlanoAcaoTaskModel $tasks = null, ?ClienteModel $clientes = null)
    {
        $this->tasks = $tasks ?: new PlanoAcaoTaskModel();
        $this->clientes = $clientes ?: new ClienteModel();
    }

    public function import(string $filePath, ?int $defaultClienteId = null): array
    {
        $rows = PlanilhaReader::load($filePath);
        if (empty($rows)) {
            throw new \RuntimeException('Planilha vazia ou sem linhas legíveis.');
        }
        $headerInfo = $this->detectHeaderRow($rows);
        $headers = $headerInfo['headers'];
        $normalized = $headerInfo['normalized'];
        $map = $headerInfo['map'];
        $rows = $headerInfo['rows'];
        if ($map['titulo'] === null) {
            AuditLogger::log('planoacao_import_header_error', 'pdca_tasks', null, [
                'headers_raw' => $headers,
                'headers_normalized' => $normalized,
                'columns_map' => $map,
            ]);
            throw new \RuntimeException('Coluna de título do plano não encontrada.');
        }
        if ($map['cliente'] === null && $map['cliente_id'] === null && !$defaultClienteId) {
            throw new \RuntimeException('Coluna de cliente não encontrada e nenhum cliente padrão informado.');
        }
        $clienteList = $this->clientes->all();
        $clientesById = [];
        $clientesByName = [];
        foreach ($clienteList as $c) {
            $id = (int)($c['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $clientesById[$id] = $c;
            $nome = (string)($c['nome_empresa'] ?? '');
            $key = $this->normalizeHeader($nome);
            if ($key !== '') {
                $clientesByName[$key] = $id;
            }
        }
        $existingByCliente = [];
        $seenKeys = [];
        $stats = [
            'total_rows' => count($rows),
            'imported' => 0,
            'ignored' => 0,
            'errors' => 0,
            'headers' => $headers,
            'columns' => $map,
            'error_rows' => [],
            'ignored_rows' => [],
            'duplicates' => [],
        ];
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $lineNumber = $index + 2;
                if ($this->isRowEmpty($row)) {
                    $stats['ignored']++;
                    $stats['ignored_rows'][] = [
                        'line' => $lineNumber,
                        'reason' => 'Linha em branco',
                    ];
                    continue;
                }
                try {
                    $result = $this->processRow($row, $map, $defaultClienteId, $clientesById, $clientesByName, $existingByCliente, $seenKeys);
                    if ($result['status'] === 'imported') {
                        $stats['imported']++;
                        AuditLogger::log('planoacao_import_ok', 'pdca_tasks', $result['task_id'], [
                            'cliente_id' => $result['cliente_id'],
                            'titulo' => $result['titulo'],
                            'line' => $lineNumber,
                        ]);
                    } elseif ($result['status'] === 'duplicate') {
                        $stats['ignored']++;
                        $stats['duplicates'][] = [
                            'line' => $lineNumber,
                            'cliente_id' => $result['cliente_id'],
                            'titulo' => $result['titulo'],
                            'reason' => $result['reason'],
                        ];
                        $stats['ignored_rows'][] = [
                            'line' => $lineNumber,
                            'reason' => $result['reason'],
                        ];
                        AuditLogger::log('planoacao_import_duplicate', 'pdca_tasks', null, [
                            'cliente_id' => $result['cliente_id'],
                            'titulo' => $result['titulo'],
                            'line' => $lineNumber,
                            'reason' => $result['reason'],
                        ]);
                    } else {
                        $stats['errors']++;
                        $stats['error_rows'][] = [
                            'line' => $lineNumber,
                            'message' => $result['message'] ?? 'Linha inválida',
                        ];
                        AuditLogger::log('planoacao_import_error', 'pdca_tasks', null, [
                            'line' => $lineNumber,
                            'message' => $result['message'] ?? 'Linha inválida',
                        ]);
                    }
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $stats['error_rows'][] = [
                        'line' => $lineNumber,
                        'message' => $e->getMessage(),
                    ];
                    AuditLogger::log('planoacao_import_exception', 'pdca_tasks', null, [
                        'line' => $lineNumber,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            AuditLogger::log('planoacao_import_fatal', 'pdca_tasks', null, ['message' => $e->getMessage()]);
            throw $e;
        }
        return $stats;
    }

    private function detectHeaderRow(array $rows): array
    {
        $limit = min(10, count($rows));
        $best = null;
        $bestScore = -1;

        for ($i = 0; $i < $limit; $i++) {
            $headers = array_map('strval', $rows[$i]);
            $normalized = [];
            foreach ($headers as $idx => $h) {
                $normalized[$idx] = $this->normalizeHeader($h);
            }
            $map = $this->mapColumns($normalized);

            $score = 0;
            if ($map['titulo'] !== null) {
                $score += 10;
            }
            if ($map['cliente'] !== null || $map['cliente_id'] !== null) {
                $score += 5;
            }
            foreach (['descricao', 'meta_valor', 'prazo', 'responsavel', 'status'] as $field) {
                if ($map[$field] !== null) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'index' => $i,
                    'headers' => $headers,
                    'normalized' => $normalized,
                    'map' => $map,
                ];
            }
        }

        if ($best === null) {
            $headers = array_map('strval', $rows[0]);
            $normalized = [];
            foreach ($headers as $idx => $h) {
                $normalized[$idx] = $this->normalizeHeader($h);
            }
            $map = $this->mapColumns($normalized);
            $best = [
                'index' => 0,
                'headers' => $headers,
                'normalized' => $normalized,
                'map' => $map,
            ];
        }

        $headerIndex = $best['index'];
        unset($rows[$headerIndex]);
        $rows = array_values($rows);
        $best['rows'] = $rows;

        AuditLogger::log('planoacao_import_header_detected', 'pdca_tasks', null, [
            'header_index' => $headerIndex,
            'headers_raw' => $best['headers'],
            'headers_normalized' => $best['normalized'],
            'columns_map' => $best['map'],
        ]);

        return $best;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }
        $map = [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ç' => 'c',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }

    private function mapColumns(array $normalizedHeaders): array
    {
        $map = [
            'cliente' => null,
            'cliente_id' => null,
            'titulo' => null,
            'descricao' => null,
            'meta_valor' => null,
            'meta_unidade' => null,
            'prazo' => null,
            'responsavel' => null,
            'status' => null,
            'progresso' => null,
        ];
        $candidates = [
            'cliente' => ['cliente', 'empresa', 'cliente_nome', 'empresa_nome'],
            'cliente_id' => ['id_cliente', 'cliente_id'],
            'titulo' => ['titulo', 'plano', 'plano_acao', 'o_que', 'oque'],
            'descricao' => ['descricao', 'descricao_plano', 'por_que', 'porque'],
            'meta_valor' => ['meta', 'como', 'solucao', 'solucoes'],
            'meta_unidade' => ['origem', 'fonte'],
            'prazo' => ['prazo', 'data_prazo', 'vencimento', 'deadline'],
            'responsavel' => ['responsavel', 'responsavel_nome'],
            'status' => ['status', 'situacao'],
            'progresso' => ['progresso', 'percentual', 'percentual_conclusao'],
        ];
        foreach ($normalizedHeaders as $index => $name) {
            foreach ($candidates as $field => $options) {
                if ($map[$field] !== null) {
                    continue;
                }
                if (in_array($name, $options, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }
        return $map;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function processRow(
        array $row,
        array $map,
        ?int $defaultClienteId,
        array $clientesById,
        array $clientesByName,
        array &$existingByCliente,
        array &$seenKeys
    ): array {
        $clienteId = $this->resolveClienteId($row, $map, $defaultClienteId, $clientesById, $clientesByName);
        $tituloIndex = $map['titulo'];
        $titulo = trim((string)($row[$tituloIndex] ?? ''));
        if ($titulo === '') {
            return [
                'status' => 'error',
                'message' => 'Título do plano vazio.',
            ];
        }
        if (!isset($existingByCliente[$clienteId])) {
            $existingByCliente[$clienteId] = [];
            $existing = $this->tasks->byCliente($clienteId);
            foreach ($existing as $task) {
                $key = $clienteId . '|' . mb_strtolower(trim((string)($task['titulo'] ?? '')));
                if ($key !== '|') {
                    $existingByCliente[$clienteId][$key] = true;
                }
            }
        }
        $key = $clienteId . '|' . mb_strtolower($titulo);
        if (isset($seenKeys[$key])) {
            return [
                'status' => 'duplicate',
                'cliente_id' => $clienteId,
                'titulo' => $titulo,
                'reason' => 'Duplicado na própria planilha.',
            ];
        }
        if (isset($existingByCliente[$clienteId][$key])) {
            return [
                'status' => 'duplicate',
                'cliente_id' => $clienteId,
                'titulo' => $titulo,
                'reason' => 'Já existe plano com mesmo título para o cliente.',
            ];
        }
        $seenKeys[$key] = true;
        $descricao = $this->readOptionalField($row, $map['descricao']);
        $metaValor = $this->readOptionalField($row, $map['meta_valor']);
        $metaUnidade = $this->readOptionalField($row, $map['meta_unidade']);
        $prazoRaw = $this->readOptionalField($row, $map['prazo']);
        $prazo = $this->parseDate($prazoRaw);
        if ($prazoRaw !== '' && $prazo === null) {
            return [
                'status' => 'error',
                'message' => 'Data de prazo inválida: ' . $prazoRaw,
            ];
        }
        $responsavel = $this->readOptionalField($row, $map['responsavel']);
        $statusRaw = $this->readOptionalField($row, $map['status']);
        $status = $this->normalizeStatus($statusRaw);
        $progressoRaw = $this->readOptionalField($row, $map['progresso']);
        $progresso = $this->parseProgresso($progressoRaw, $status);
        $data = [
            'id_cliente' => $clienteId,
            'titulo' => $titulo,
            'descricao' => $descricao !== '' ? $descricao : null,
            'meta_valor' => $metaValor !== '' ? $metaValor : null,
            'meta_unidade' => $metaUnidade !== '' ? $metaUnidade : null,
            'prazo' => $prazo,
            'responsavel' => $responsavel !== '' ? $responsavel : null,
            'fase' => 'DO',
            'status' => $status,
            'progresso' => $progresso,
        ];
        $taskId = $this->tasks->create($data);
        return [
            'status' => 'imported',
            'task_id' => $taskId,
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
        ];
    }

    private function resolveClienteId(
        array $row,
        array $map,
        ?int $defaultClienteId,
        array $clientesById,
        array $clientesByName
    ): int {
        if ($map['cliente_id'] !== null) {
            $raw = trim((string)($row[$map['cliente_id']] ?? ''));
            if ($raw === '') {
                throw new \RuntimeException('ID do cliente vazio.');
            }
            $id = (int)$raw;
            if ($id <= 0 || !isset($clientesById[$id])) {
                throw new \RuntimeException('Cliente não encontrado para ID: ' . $raw);
            }
            return $id;
        }
        if ($map['cliente'] !== null) {
            $nome = trim((string)($row[$map['cliente']] ?? ''));
            if ($nome === '') {
                throw new \RuntimeException('Nome do cliente vazio.');
            }
            $key = $this->normalizeHeader($nome);
            if (!isset($clientesByName[$key])) {
                throw new \RuntimeException('Cliente não encontrado para nome: ' . $nome);
            }
            return $clientesByName[$key];
        }
        if ($defaultClienteId) {
            if (!isset($clientesById[$defaultClienteId])) {
                throw new \RuntimeException('Cliente padrão informado é inválido.');
            }
            return $defaultClienteId;
        }
        throw new \RuntimeException('Cliente não informado.');
    }

    private function readOptionalField(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }
        return trim((string)($row[$index] ?? ''));
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $dt = \DateTime::createFromFormat('d/m/Y', $value);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }
        if (is_numeric($value)) {
            $base = new \DateTime('1899-12-30');
            $base->modify('+' . (int)$value . ' days');
            return $base->format('Y-m-d');
        }
        return null;
    }

    private function normalizeStatus(string $raw): string
    {
        $raw = trim(mb_strtolower($raw));
        if ($raw === '') {
            return 'Planejado';
        }
        $map = [
            'a_fazer' => 'Planejado',
            'a-fazer' => 'Planejado',
            'afazer' => 'Planejado',
            'novo' => 'Planejado',
            'em_andamento' => 'Em Andamento',
            'em-andamento' => 'Em Andamento',
            'emandamento' => 'Em Andamento',
            'andamento' => 'Em Andamento',
            'concluido' => 'Concluído',
            'concluido_' => 'Concluído',
            'feito' => 'Concluído',
            'ok' => 'Concluído',
            'pendente' => 'Pendente',
        ];
        $key = preg_replace('/[^a-z]+/', '_', $raw);
        $key = trim($key, '_');
        if (isset($map[$key])) {
            return $map[$key];
        }
        $known = ['a fazer', 'em andamento', 'concluído', 'concluido', 'pendente'];
        if (in_array($raw, $known, true)) {
            if ($raw === 'a fazer') {
                return 'Planejado';
            }
            if ($raw === 'em andamento') {
                return 'Em Andamento';
            }
            if ($raw === 'concluído' || $raw === 'concluido') {
                return 'Concluído';
            }
            if ($raw === 'pendente') {
                return 'Pendente';
            }
        }
        return 'Planejado';
    }

    private function parseProgresso(string $raw, string $status): int
    {
        $raw = trim($raw);
        if ($raw === '') {
            if ($status === 'Concluído') {
                return 100;
            }
            return 0;
        }
        $value = str_replace(['%', ','], ['', '.'], $raw);
        if (!is_numeric($value)) {
            if ($status === 'Concluído') {
                return 100;
            }
            return 0;
        }
        $n = (float)$value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 100) {
            $n = 100;
        }
        return (int)round($n);
    }
}

<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;
use PDO;
use RuntimeException;

class ColaboradorImportService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function import(string $tmpPath, string $clientFilename, int $userId): array
    {
        (new ColaboradorModel())->countByClientesWithFilters([0], []);
        (new DepartamentoModel())->all();
        (new SetorModel())->all();
        (new FuncaoModel())->allByClientes([0]);

        $rows = $this->readRows($tmpPath, $clientFilename);
        if (!empty($rows['error'])) {
            return ['ok' => false, 'inserted' => 0, 'errors' => [$rows['error']]];
        }
        $headerMap = $rows['headerMap'];
        $iter = $rows['iter'];

        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->all();
        $clienteByName = [];
        $clienteMeta = [];
        foreach ($clientes as $c) {
            $id = (int)($c['id'] ?? 0);
            $name = (string)($c['nome_empresa'] ?? '');
            $key = $this->normalizeName($name);
            if ($key !== '' && !isset($clienteByName[$key])) {
                $clienteByName[$key] = $id;
            }
            if ($id > 0) {
                $clienteMeta[$id] = [
                    'is_matriz' => (int)($c['is_matriz'] ?? 1),
                    'matriz_id' => (int)($c['matriz_id'] ?? 0),
                ];
            }
        }

        $errors = [];
        $inserted = 0;
        $seenDoc = [];
        $seenEmail = [];

        $stmtExistsDoc = $this->pdo->prepare('SELECT 1 FROM colaboradores WHERE documento = :doc LIMIT 1');
        $stmtExistsEmail = $this->pdo->prepare('SELECT 1 FROM colaboradores WHERE email = :email LIMIT 1');
        $stmtFindDept = $this->pdo->prepare('SELECT id FROM departamentos WHERE cliente_id = :cid AND nome = :nome AND ativo = 1 LIMIT 1');
        $stmtInsertDept = $this->pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
        $stmtFindSetor = $this->pdo->prepare('SELECT id FROM setores WHERE departamento_id = :dep AND nome = :nome AND ativo = 1 LIMIT 1');
        $stmtInsertSetor = $this->pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :dep)');
        $stmtFindFuncao = $this->pdo->prepare('SELECT id FROM funcoes WHERE setor_id = :setor AND nome = :nome AND ativo = 1 LIMIT 1');
        $stmtInsertFuncao = $this->pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor)');
        $stmtInsertCol = $this->pdo->prepare('INSERT INTO colaboradores (nome, email, documento, data_nascimento, celular, funcao_id, lider, cliente_id, ativo) VALUES (:nome, :email, :documento, :data_nascimento, :celular, :funcao_id, :lider, :cliente_id, :ativo)');

        $catalogRootCache = [];
        $this->pdo->beginTransaction();
        try {
            foreach ($iter as $row) {
                $line = $row['line'];
                $raw = $row['raw'];
                $normalized = $this->normalizeRow($raw, $headerMap);
                $rowErrors = $this->validateRow($normalized, $line);
                if (!empty($rowErrors)) {
                    $errors = array_merge($errors, $rowErrors);
                    continue;
                }

                $unidadeKey = $this->normalizeName($normalized['unidade']);
                $clienteId = $clienteByName[$unidadeKey] ?? 0;
                if ($clienteId <= 0) {
                    $errors[] = ['line' => $line, 'field' => 'Unidade', 'message' => 'Unidade não encontrada no sistema.', 'value' => $normalized['unidade']];
                    continue;
                }
                if (!Auth::canAccessCliente($clienteId)) {
                    $errors[] = ['line' => $line, 'field' => 'Unidade', 'message' => 'Sem permissão para importar para a unidade informada.', 'value' => $normalized['unidade']];
                    continue;
                }

                $meta = $clienteMeta[$clienteId] ?? ['is_matriz' => 1, 'matriz_id' => 0];
                $isFilial = (int)($meta['is_matriz'] ?? 1) !== 1 && (int)($meta['matriz_id'] ?? 0) > 0;
                $catalogClienteId = $catalogRootCache[$clienteId] ?? ($catalogRootCache[$clienteId] = $this->catalogRootIdForCliente($clienteId, $clienteMeta));
                $allowCatalogCreate = !$isFilial || $catalogClienteId === $clienteId;

                $doc = $normalized['documento_digits'];
                $email = $normalized['email_lower'];
                if (isset($seenDoc[$doc])) {
                    $errors[] = ['line' => $line, 'field' => 'Documento', 'message' => 'Documento duplicado no arquivo.', 'value' => $normalized['documento']];
                    continue;
                }
                if (isset($seenEmail[$email])) {
                    $errors[] = ['line' => $line, 'field' => 'Email', 'message' => 'Email duplicado no arquivo.', 'value' => $normalized['email']];
                    continue;
                }
                $seenDoc[$doc] = true;
                $seenEmail[$email] = true;

                $stmtExistsDoc->execute(['doc' => $doc]);
                if ($stmtExistsDoc->fetchColumn()) {
                    $errors[] = ['line' => $line, 'field' => 'Documento', 'message' => 'Documento já cadastrado.', 'value' => $normalized['documento']];
                    continue;
                }
                $stmtExistsEmail->execute(['email' => $email]);
                if ($stmtExistsEmail->fetchColumn()) {
                    $errors[] = ['line' => $line, 'field' => 'Email', 'message' => 'Email já cadastrado.', 'value' => $normalized['email']];
                    continue;
                }

                $departamentoId = $this->getOrCreateDepartamento($catalogClienteId, $normalized['departamento'], $allowCatalogCreate, $stmtFindDept, $stmtInsertDept);
                if ($departamentoId <= 0) {
                    $errors[] = [
                        'line' => $line,
                        'field' => 'Departamento',
                        'message' => $isFilial ? 'Departamento não encontrado no catálogo da matriz.' : 'Falha ao resolver Departamento.',
                        'value' => $normalized['departamento'],
                    ];
                    if ($isFilial) {
                        AuditLogger::log('colab_import_catalog_violation', 'departamentos', null, [
                            'cliente_id' => $clienteId,
                            'catalog_cliente_id' => $catalogClienteId,
                            'departamento' => $normalized['departamento'],
                            'line' => $line,
                        ]);
                    }
                    continue;
                }

                $setorId = $this->getOrCreateSetor($departamentoId, $normalized['setor'], $allowCatalogCreate, $stmtFindSetor, $stmtInsertSetor);
                if ($setorId <= 0) {
                    $errors[] = [
                        'line' => $line,
                        'field' => 'Setor',
                        'message' => $isFilial ? 'Setor não encontrado no catálogo da matriz.' : 'Falha ao resolver Setor.',
                        'value' => $normalized['setor'],
                    ];
                    if ($isFilial) {
                        AuditLogger::log('colab_import_catalog_violation', 'setores', null, [
                            'cliente_id' => $clienteId,
                            'catalog_cliente_id' => $catalogClienteId,
                            'departamento_id' => $departamentoId,
                            'setor' => $normalized['setor'],
                            'line' => $line,
                        ]);
                    }
                    continue;
                }

                $funcaoId = $this->getOrCreateFuncao($setorId, $normalized['funcao'], $allowCatalogCreate, $stmtFindFuncao, $stmtInsertFuncao);
                if ($funcaoId <= 0) {
                    $errors[] = [
                        'line' => $line,
                        'field' => 'Função',
                        'message' => $isFilial ? 'Função não encontrada no catálogo da matriz.' : 'Falha ao resolver Função.',
                        'value' => $normalized['funcao'],
                    ];
                    if ($isFilial) {
                        AuditLogger::log('colab_import_catalog_violation', 'funcoes', null, [
                            'cliente_id' => $clienteId,
                            'catalog_cliente_id' => $catalogClienteId,
                            'setor_id' => $setorId,
                            'funcao' => $normalized['funcao'],
                            'line' => $line,
                        ]);
                    }
                    continue;
                }

                $stmtInsertCol->execute([
                    'nome' => $normalized['nome'],
                    'email' => $email,
                    'documento' => $doc,
                    'data_nascimento' => $normalized['data_nascimento_db'],
                    'celular' => $normalized['celular'],
                    'funcao_id' => $funcaoId,
                    'lider' => 'não',
                    'cliente_id' => $clienteId,
                    'ativo' => 1,
                ]);
                $inserted++;
            }

            if (!empty($errors)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'inserted' => 0, 'errors' => $errors];
            }
            $this->pdo->commit();
            return ['ok' => true, 'inserted' => $inserted, 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            AuditLogger::log('colab_import_error', 'colaboradores', null, [
                'error' => $e->getMessage(),
                'file' => $clientFilename,
            ]);
            return [
                'ok' => false,
                'inserted' => 0,
                'errors' => [
                    ['line' => 0, 'field' => '_', 'message' => 'Erro interno ao processar importação.', 'value' => get_class($e)],
                ],
            ];
        }
    }

    private function getOrCreateDepartamento(int $clienteId, string $nome, bool $allowCreate, \PDOStatement $find, \PDOStatement $insert): int
    {
        $find->execute(['cid' => $clienteId, 'nome' => $nome]);
        $id = (int)$find->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        if (!$allowCreate) {
            return 0;
        }
        try {
            $insert->execute(['nome' => $nome, 'cid' => $clienteId]);
        } catch (\PDOException $e) {}
        $find->execute(['cid' => $clienteId, 'nome' => $nome]);
        return (int)$find->fetchColumn();
    }

    private function getOrCreateSetor(int $departamentoId, string $nome, bool $allowCreate, \PDOStatement $find, \PDOStatement $insert): int
    {
        $find->execute(['dep' => $departamentoId, 'nome' => $nome]);
        $id = (int)$find->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        if (!$allowCreate) {
            return 0;
        }
        try {
            $insert->execute(['nome' => $nome, 'dep' => $departamentoId]);
        } catch (\PDOException $e) {}
        $find->execute(['dep' => $departamentoId, 'nome' => $nome]);
        return (int)$find->fetchColumn();
    }

    private function getOrCreateFuncao(int $setorId, string $nome, bool $allowCreate, \PDOStatement $find, \PDOStatement $insert): int
    {
        $find->execute(['setor' => $setorId, 'nome' => $nome]);
        $id = (int)$find->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        if (!$allowCreate) {
            return 0;
        }
        try {
            $insert->execute(['nome' => $nome, 'setor' => $setorId]);
        } catch (\PDOException $e) {}
        $find->execute(['setor' => $setorId, 'nome' => $nome]);
        return (int)$find->fetchColumn();
    }

    private function catalogRootIdForCliente(int $clienteId, array $clienteMeta): int
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return 0;
        }
        $meta = $clienteMeta[$clienteId] ?? null;
        if (!$meta) {
            return $clienteId;
        }
        $matrizId = (int)($meta['matriz_id'] ?? 0);
        $isMatriz = $matrizId <= 0 && (int)($meta['is_matriz'] ?? 1) === 1;
        $rootId = $isMatriz ? $clienteId : $matrizId;
        if ($rootId <= 0) {
            $rootId = $clienteId;
        }
        if ($rootId !== $clienteId && !isset($clienteMeta[$rootId])) {
            $rootId = $clienteId;
        }
        return $rootId;
    }

    private function validateRow(array $row, int $line): array
    {
        $errors = [];

        if ($row['nome'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Nome', 'message' => 'Nome é obrigatório.', 'value' => ''];
        } elseif (mb_strlen($row['nome']) > 100) {
            $errors[] = ['line' => $line, 'field' => 'Nome', 'message' => 'Nome excede 100 caracteres.', 'value' => $row['nome']];
        }

        if ($row['documento'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Documento', 'message' => 'Documento é obrigatório.', 'value' => ''];
        } else {
            $digits = $row['documento_digits'];
            if ($digits === '') {
                $errors[] = ['line' => $line, 'field' => 'Documento', 'message' => 'Documento inválido.', 'value' => $row['documento']];
            } elseif (!$this->isCpfOrCnpjValid($digits)) {
                $errors[] = ['line' => $line, 'field' => 'Documento', 'message' => 'CPF/CNPJ inválido.', 'value' => $row['documento']];
            }
        }

        if ($row['dn'] === '') {
            $errors[] = ['line' => $line, 'field' => 'DN', 'message' => 'Data de nascimento é obrigatória.', 'value' => ''];
        } else {
            $db = $this->parseBrDateToDb($row['dn']);
            if ($db === null) {
                $errors[] = ['line' => $line, 'field' => 'DN', 'message' => 'Data de nascimento inválida. Use DD/MM/AAAA.', 'value' => $row['dn']];
            } else {
                $row['data_nascimento_db'] = $db;
            }
        }

        if ($row['celular'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Celular', 'message' => 'Celular é obrigatório.', 'value' => ''];
        } elseif (mb_strlen($row['celular']) > 15) {
            $errors[] = ['line' => $line, 'field' => 'Celular', 'message' => 'Celular excede 15 caracteres.', 'value' => $row['celular']];
        }

        if ($row['email'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Email', 'message' => 'Email é obrigatório.', 'value' => ''];
        } elseif (mb_strlen($row['email']) > 180) {
            $errors[] = ['line' => $line, 'field' => 'Email', 'message' => 'Email excede 180 caracteres.', 'value' => $row['email']];
        } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['line' => $line, 'field' => 'Email', 'message' => 'Email inválido.', 'value' => $row['email']];
        }

        if ($row['unidade'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Unidade', 'message' => 'Unidade é obrigatória.', 'value' => ''];
        } elseif (mb_strlen($row['unidade']) > 50) {
            $errors[] = ['line' => $line, 'field' => 'Unidade', 'message' => 'Unidade excede 50 caracteres.', 'value' => $row['unidade']];
        }

        if ($row['funcao'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Função', 'message' => 'Função é obrigatória.', 'value' => ''];
        } elseif (mb_strlen($row['funcao']) > 50) {
            $errors[] = ['line' => $line, 'field' => 'Função', 'message' => 'Função excede 50 caracteres.', 'value' => $row['funcao']];
        }

        if ($row['setor'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Setor', 'message' => 'Setor é obrigatório.', 'value' => ''];
        } elseif (mb_strlen($row['setor']) > 50) {
            $errors[] = ['line' => $line, 'field' => 'Setor', 'message' => 'Setor excede 50 caracteres.', 'value' => $row['setor']];
        }

        if ($row['departamento'] === '') {
            $errors[] = ['line' => $line, 'field' => 'Departamento', 'message' => 'Departamento é obrigatório.', 'value' => ''];
        } elseif (mb_strlen($row['departamento']) > 50) {
            $errors[] = ['line' => $line, 'field' => 'Departamento', 'message' => 'Departamento excede 50 caracteres.', 'value' => $row['departamento']];
        }

        return $errors;
    }

    private function normalizeRow(array $raw, array $headerMap): array
    {
        $get = static function(array $raw, array $map, string $key): string {
            $idx = $map[$key] ?? null;
            if ($idx === null) {
                return '';
            }
            $v = $raw[$idx] ?? '';
            if ($v === null) {
                return '';
            }
            return trim((string)$v);
        };

        $doc = $get($raw, $headerMap, 'documento');
        $email = $get($raw, $headerMap, 'email');
        $dn = $get($raw, $headerMap, 'dn');
        $cel = $get($raw, $headerMap, 'celular');

        $digits = preg_replace('/\D+/', '', $doc) ?: '';
        $emailLower = mb_strtolower(trim($email));

        $dbDate = $this->parseBrDateToDb($dn);

        return [
            'nome' => $get($raw, $headerMap, 'nome'),
            'documento' => $doc,
            'documento_digits' => $digits,
            'dn' => $dn,
            'data_nascimento_db' => $dbDate ?? null,
            'celular' => $cel,
            'email' => $email,
            'email_lower' => $emailLower,
            'unidade' => $get($raw, $headerMap, 'unidade'),
            'funcao' => $get($raw, $headerMap, 'funcao'),
            'setor' => $get($raw, $headerMap, 'setor'),
            'departamento' => $get($raw, $headerMap, 'departamento'),
        ];
    }

    private function readRows(string $tmpPath, string $clientFilename): array
    {
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return $this->readCsv($tmpPath);
        }
        if ($ext === 'xlsx') {
            return $this->readXlsx($tmpPath);
        }
        if ($ext === 'xls') {
            return $this->readXls($tmpPath);
        }
        return ['error' => ['line' => 0, 'field' => '_', 'message' => 'Formato inválido. Use CSV, XLS ou XLSX.', 'value' => $clientFilename]];
    }

    private function expectedHeader(): array
    {
        return [
            'nome' => 'Nome',
            'documento' => 'Documento',
            'dn' => 'DN',
            'celular' => 'Celular',
            'email' => 'Email',
            'unidade' => 'Unidade',
            'funcao' => 'Função',
            'setor' => 'Setor',
            'departamento' => 'Departamento',
        ];
    }

    private function buildHeaderMap(array $header): array
    {
        $expected = $this->expectedHeader();
        $map = [];
        $normalizedHeader = [];
        foreach ($header as $i => $h) {
            $normalizedHeader[$i] = $this->normalizeName((string)$h);
        }
        foreach ($expected as $key => $label) {
            $wanted = $this->normalizeName($label);
            $idx = array_search($wanted, $normalizedHeader, true);
            if ($idx === false) {
                return [];
            }
            $map[$key] = (int)$idx;
        }
        return $map;
    }

    private function readCsv(string $tmpPath): array
    {
        $file = new \SplFileObject($tmpPath, 'rb');
        $file->setFlags(\SplFileObject::READ_CSV);
        $file->setCsvControl(',', '"', '\\');

        $first = $file->fgetcsv();
        if (!is_array($first) || count($first) === 0) {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'CSV vazio.', 'value' => '']];
        }
        $rawLine = implode(',', array_map(static fn($v) => (string)$v, $first));
        $semicolonCount = substr_count($rawLine, ';');
        $commaCount = substr_count($rawLine, ',');
        if ($semicolonCount > $commaCount) {
            $file->rewind();
            $file->setCsvControl(';', '"', '\\');
            $first = $file->fgetcsv();
        }

        $headerMap = $this->buildHeaderMap($first);
        if (empty($headerMap)) {
            return ['error' => ['line' => 1, 'field' => 'Header', 'message' => 'Cabeçalho inválido. Colunas obrigatórias: Nome, Documento, DN, Celular, Email, Unidade, Função, Setor, Departamento.', 'value' => json_encode($first, JSON_UNESCAPED_UNICODE)]];
        }

        $iter = (function() use ($file) {
            $line = 1;
            while (!$file->eof()) {
                $line++;
                $row = $file->fgetcsv();
                if (!is_array($row)) {
                    continue;
                }
                $allEmpty = true;
                foreach ($row as $v) {
                    if (trim((string)$v) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) {
                    continue;
                }
                yield ['line' => $line, 'raw' => $row];
            }
        })();

        return ['headerMap' => $headerMap, 'iter' => $iter];
    }

    private function readXls(string $tmpPath): array
    {
        $content = file_get_contents($tmpPath);
        if ($content === false) {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'Não foi possível ler o arquivo.', 'value' => '']];
        }
        $trim = ltrim($content);
        if ($trim !== '' && $trim[0] === '<' && stripos($content, '<table') !== false) {
            return $this->readHtmlTable($content);
        }
        return ['error' => ['line' => 0, 'field' => '_', 'message' => 'XLS binário não suportado. Exporte como XLSX ou CSV.', 'value' => '']];
    }

    private function readHtmlTable(string $html): array
    {
        $html = trim($html);
        if ($html !== '' && stripos($html, 'charset=') === false) {
            $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $html;
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            if (is_string($converted) && $converted !== '') {
                $html = $converted;
            }
        }
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'Tabela não encontrada no arquivo.', 'value' => '']];
        }
        $table = $tables->item(0);
        $rows = $table->getElementsByTagName('tr');
        if ($rows->length === 0) {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'Arquivo sem linhas.', 'value' => '']];
        }

        $header = [];
        $firstRow = $rows->item(0);
        foreach ($firstRow->childNodes as $cell) {
            if (!($cell instanceof \DOMElement)) { continue; }
            if (!in_array(strtolower($cell->tagName), ['th', 'td'], true)) { continue; }
            $header[] = trim($cell->textContent ?? '');
        }

        $headerMap = $this->buildHeaderMap($header);
        if (empty($headerMap)) {
            return ['error' => ['line' => 1, 'field' => 'Header', 'message' => 'Cabeçalho inválido. Colunas obrigatórias: Nome, Documento, DN, Celular, Email, Unidade, Função, Setor, Departamento.', 'value' => json_encode($header, JSON_UNESCAPED_UNICODE)]];
        }

        $iter = (function() use ($rows) {
            $line = 1;
            for ($i = 1; $i < $rows->length; $i++) {
                $line++;
                $tr = $rows->item($i);
                $cols = [];
                foreach ($tr->childNodes as $cell) {
                    if (!($cell instanceof \DOMElement)) { continue; }
                    if (!in_array(strtolower($cell->tagName), ['th', 'td'], true)) { continue; }
                    $cols[] = trim($cell->textContent ?? '');
                }
                $allEmpty = true;
                foreach ($cols as $v) {
                    if (trim((string)$v) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) {
                    continue;
                }
                yield ['line' => $line, 'raw' => $cols];
            }
        })();

        return ['headerMap' => $headerMap, 'iter' => $iter];
    }

    private function readXlsx(string $tmpPath): array
    {
        $sharedXml = '';
        $sheetXml = '';
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return ['error' => ['line' => 0, 'field' => '_', 'message' => 'XLSX inválido.', 'value' => '']];
            }
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml') ?: '';
            $zip->close();
        } else {
            $sharedXml = $this->zipGetEntry($tmpPath, 'xl/sharedStrings.xml') ?: '';
            $sheetXml = $this->zipGetEntry($tmpPath, 'xl/worksheets/sheet1.xml') ?: '';
        }
        if ($sheetXml === '') {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'XLSX sem planilha sheet1.', 'value' => '']];
        }

        $shared = $this->parseSharedStrings($sharedXml);
        $rows = $this->parseSheetRows($sheetXml, $shared);
        if (empty($rows)) {
            return ['error' => ['line' => 0, 'field' => '_', 'message' => 'XLSX vazio.', 'value' => '']];
        }
        $header = $rows[0];
        $headerMap = $this->buildHeaderMap($header);
        if (empty($headerMap)) {
            return ['error' => ['line' => 1, 'field' => 'Header', 'message' => 'Cabeçalho inválido. Colunas obrigatórias: Nome, Documento, DN, Celular, Email, Unidade, Função, Setor, Departamento.', 'value' => json_encode($header, JSON_UNESCAPED_UNICODE)]];
        }
        $iter = (function() use ($rows) {
            $line = 1;
            for ($i = 1; $i < count($rows); $i++) {
                $line++;
                $r = $rows[$i];
                $allEmpty = true;
                foreach ($r as $v) {
                    if (trim((string)$v) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) { continue; }
                yield ['line' => $line, 'raw' => $r];
            }
        })();
        return ['headerMap' => $headerMap, 'iter' => $iter];
    }

    private function zipGetEntry(string $zipPath, string $entryName): ?string
    {
        $fp = @fopen($zipPath, 'rb');
        if (!$fp) {
            return null;
        }
        $stat = fstat($fp);
        $size = (int)($stat['size'] ?? 0);
        if ($size <= 0) {
            fclose($fp);
            return null;
        }
        $maxEocd = min(65557, $size);
        fseek($fp, $size - $maxEocd);
        $tail = fread($fp, $maxEocd);
        if (!is_string($tail) || $tail === '') {
            fclose($fp);
            return null;
        }
        $sig = "\x50\x4b\x05\x06";
        $pos = strrpos($tail, $sig);
        if ($pos === false) {
            fclose($fp);
            return null;
        }
        $eocd = substr($tail, $pos);
        if (strlen($eocd) < 22) {
            fclose($fp);
            return null;
        }
        $cdSize = unpack('V', substr($eocd, 12, 4))[1] ?? 0;
        $cdOffset = unpack('V', substr($eocd, 16, 4))[1] ?? 0;
        if ($cdSize <= 0 || $cdOffset <= 0) {
            fclose($fp);
            return null;
        }
        fseek($fp, $cdOffset);
        $cd = fread($fp, $cdSize);
        if (!is_string($cd) || $cd === '') {
            fclose($fp);
            return null;
        }
        $p = 0;
        $wantedOffset = null;
        $wantedCompMethod = null;
        $wantedCompSize = null;
        $wantedUncompSize = null;
        while ($p + 46 <= strlen($cd)) {
            if (substr($cd, $p, 4) !== "\x50\x4b\x01\x02") {
                break;
            }
            $compMethod = unpack('v', substr($cd, $p + 10, 2))[1] ?? 0;
            $crc = unpack('V', substr($cd, $p + 16, 4))[1] ?? 0;
            $compSize = unpack('V', substr($cd, $p + 20, 4))[1] ?? 0;
            $uncompSize = unpack('V', substr($cd, $p + 24, 4))[1] ?? 0;
            $nameLen = unpack('v', substr($cd, $p + 28, 2))[1] ?? 0;
            $extraLen = unpack('v', substr($cd, $p + 30, 2))[1] ?? 0;
            $commentLen = unpack('v', substr($cd, $p + 32, 2))[1] ?? 0;
            $localOffset = unpack('V', substr($cd, $p + 42, 4))[1] ?? 0;
            $name = substr($cd, $p + 46, $nameLen);
            if ($name === $entryName) {
                $wantedOffset = $localOffset;
                $wantedCompMethod = $compMethod;
                $wantedCompSize = $compSize;
                $wantedUncompSize = $uncompSize;
                break;
            }
            $p += 46 + $nameLen + $extraLen + $commentLen;
        }
        if ($wantedOffset === null) {
            fclose($fp);
            return null;
        }
        fseek($fp, $wantedOffset);
        $lh = fread($fp, 30);
        if (!is_string($lh) || strlen($lh) < 30 || substr($lh, 0, 4) !== "\x50\x4b\x03\x04") {
            fclose($fp);
            return null;
        }
        $nameLen = unpack('v', substr($lh, 26, 2))[1] ?? 0;
        $extraLen = unpack('v', substr($lh, 28, 2))[1] ?? 0;
        fseek($fp, $wantedOffset + 30 + $nameLen + $extraLen);
        $data = fread($fp, (int)$wantedCompSize);
        fclose($fp);
        if (!is_string($data)) {
            return null;
        }
        if ((int)$wantedCompMethod === 0) {
            return $data;
        }
        if ((int)$wantedCompMethod === 8) {
            $out = @gzinflate($data);
            return is_string($out) ? $out : null;
        }
        return null;
    }

    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') { return []; }
        $sxml = @simplexml_load_string($xml);
        if (!$sxml) { return []; }
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sxml->registerXPathNamespace('a', $ns);
        $out = [];
        $nodes = $sxml->xpath('//a:si') ?: [];
        foreach ($nodes as $si) {
            $t = $si->children($ns)->t ?? null;
            if ($t !== null) {
                $out[] = (string)$t;
                continue;
            }
            $rNodes = $si->children($ns)->r ?? null;
            if ($rNodes !== null) {
                $text = '';
                foreach ($si->children($ns)->r as $r) {
                    $text .= (string)($r->children($ns)->t ?? '');
                }
                $out[] = $text;
                continue;
            }
            $out[] = '';
        }
        return $out;
    }

    private function parseSheetRows(string $xml, array $sharedStrings): array
    {
        $sxml = @simplexml_load_string($xml);
        if (!$sxml) {
            return [];
        }
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sxml->registerXPathNamespace('a', $ns);
        $rows = [];
        $rowNodes = $sxml->xpath('//a:sheetData/a:row') ?: [];
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $rowNode->registerXPathNamespace('a', $ns);
            $cellNodes = $rowNode->xpath('a:c') ?: [];
            $maxCol = 0;
            foreach ($cellNodes as $c) {
                $r = (string)($c['r'] ?? '');
                $col = $this->colIndexFromRef($r);
                $maxCol = max($maxCol, $col);
            }
            for ($i = 0; $i <= $maxCol; $i++) { $cells[$i] = ''; }
            foreach ($cellNodes as $c) {
                $ref = (string)($c['r'] ?? '');
                $col = $this->colIndexFromRef($ref);
                $type = (string)($c['t'] ?? '');
                $v = (string)($c->children($ns)->v ?? '');
                if ($type === 's') {
                    $idx = (int)$v;
                    $cells[$col] = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $is = $c->children($ns)->is ?? null;
                    $cells[$col] = $is ? (string)($is->children($ns)->t ?? '') : '';
                } else {
                    $cells[$col] = $v;
                }
            }
            $rows[] = array_values($cells);
        }
        return $rows;
    }

    private function colIndexFromRef(string $ref): int
    {
        if ($ref === '') { return 0; }
        $letters = preg_replace('/[^A-Z]+/i', '', $ref) ?: '';
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $n - 1);
    }

    private function parseBrDateToDb(string $br): ?string
    {
        $br = trim($br);
        if ($br === '') { return null; }
        if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $br, $m)) {
            return null;
        }
        $d = (int)$m[1];
        $mo = (int)$m[2];
        $y = (int)$m[3];
        if (!checkdate($mo, $d, $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    private function normalizeName(string $name): string
    {
        $name = trim(mb_strtolower($name));
        if ($name === '') { return ''; }
        $name = preg_replace('/\s+/', ' ', $name) ?: '';
        if (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if (is_string($t) && $t !== '') {
                $name = $t;
            }
        }
        $name = preg_replace('/[^a-z0-9 ]+/', '', $name) ?: '';
        $name = preg_replace('/\s+/', ' ', $name) ?: '';
        return trim($name);
    }

    private function isCpfOrCnpjValid(string $digits): bool
    {
        $digits = preg_replace('/\D+/', '', $digits) ?: '';
        if (strlen($digits) === 11) {
            return $this->isCpfValid($digits);
        }
        if (strlen($digits) === 14) {
            return $this->isCnpjValid($digits);
        }
        return false;
    }

    private function isCpfValid(string $cpf): bool
    {
        if (!preg_match('/^\d{11}$/', $cpf)) { return false; }
        if (preg_match('/^(\d)\1{10}$/', $cpf)) { return false; }
        $sum = 0;
        for ($i = 0, $w = 10; $i < 9; $i++, $w--) {
            $sum += ((int)$cpf[$i]) * $w;
        }
        $d1 = 11 - ($sum % 11);
        $d1 = $d1 >= 10 ? 0 : $d1;
        if ($d1 !== (int)$cpf[9]) { return false; }
        $sum = 0;
        for ($i = 0, $w = 11; $i < 10; $i++, $w--) {
            $sum += ((int)$cpf[$i]) * $w;
        }
        $d2 = 11 - ($sum % 11);
        $d2 = $d2 >= 10 ? 0 : $d2;
        return $d2 === (int)$cpf[10];
    }

    private function isCnpjValid(string $cnpj): bool
    {
        if (!preg_match('/^\d{14}$/', $cnpj)) { return false; }
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) { return false; }
        $w1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $w2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int)$cnpj[$i]) * $w1[$i];
        }
        $d1 = $sum % 11;
        $d1 = $d1 < 2 ? 0 : 11 - $d1;
        if ($d1 !== (int)$cnpj[12]) { return false; }
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += ((int)$cnpj[$i]) * $w2[$i];
        }
        $d2 = $sum % 11;
        $d2 = $d2 < 2 ? 0 : 11 - $d2;
        return $d2 === (int)$cnpj[13];
    }
}

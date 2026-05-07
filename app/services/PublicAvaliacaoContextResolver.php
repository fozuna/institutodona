<?php
namespace App\Services;

use App\Models\ClienteModel;

class PublicAvaliacaoContextResolver
{
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
    }

    public function resolveFromCurrentHost(): ?array
    {
        $host = $this->normalizeHost((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return $this->resolveDefault();
        }

        $cliente = $this->clientes->findByPublicHost($host);
        if ($cliente) {
            return [
                'host' => $host,
                'cliente_id' => (int)($cliente['id'] ?? 0),
                'empresa_nome' => (string)($cliente['nome_empresa'] ?? ''),
                'logo_path' => (string)($cliente['logo_path'] ?? ''),
                'source' => 'clientes.dominio_publico',
            ];
        }

        $mapped = $this->resolveFromEnvMap($host);
        if ($mapped) {
            return $mapped + ['host' => $host, 'source' => 'PUBLIC_AVALIACOES_CONTEXT_MAP'];
        }

        return $this->resolveDefault($host);
    }

    private function resolveFromEnvMap(string $host): ?array
    {
        $raw = trim((string)(getenv('PUBLIC_AVALIACOES_CONTEXT_MAP') ?: ''));
        if ($raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }
        $context = $decoded[$host] ?? $decoded['www.' . $host] ?? null;
        if (!is_array($context)) {
            return null;
        }
        $clienteId = (int)($context['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $cliente = $this->clientes->find($clienteId);
            if ($cliente) {
                return [
                    'cliente_id' => $clienteId,
                    'empresa_nome' => (string)($cliente['nome_empresa'] ?? ($context['empresa_nome'] ?? '')),
                    'logo_path' => (string)($cliente['logo_path'] ?? ''),
                ];
            }
        }
        $empresa = trim((string)($context['empresa_nome'] ?? ''));
        if ($empresa === '') {
            return null;
        }
        return [
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'empresa_nome' => $empresa,
            'logo_path' => '',
        ];
    }

    private function resolveDefault(string $host = ''): ?array
    {
        $clienteId = (int)(getenv('PUBLIC_AVALIACOES_DEFAULT_CLIENTE_ID') ?: 0);
        if ($clienteId > 0) {
            $cliente = $this->clientes->find($clienteId);
            if ($cliente) {
                return [
                    'host' => $host,
                    'cliente_id' => $clienteId,
                    'empresa_nome' => (string)($cliente['nome_empresa'] ?? ''),
                    'logo_path' => (string)($cliente['logo_path'] ?? ''),
                    'source' => 'PUBLIC_AVALIACOES_DEFAULT_CLIENTE_ID',
                ];
            }
        }
        $empresa = trim((string)(getenv('PUBLIC_AVALIACOES_DEFAULT_EMPRESA') ?: ''));
        if ($empresa !== '') {
            return [
                'host' => $host,
                'cliente_id' => null,
                'empresa_nome' => $empresa,
                'logo_path' => '',
                'source' => 'PUBLIC_AVALIACOES_DEFAULT_EMPRESA',
            ];
        }

        $empresa = $this->deriveEmpresaFromHost($host);
        return [
            'host' => $host,
            'cliente_id' => null,
            'empresa_nome' => $empresa,
            'logo_path' => '',
            'source' => 'host-derived-fallback',
        ];
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        return preg_replace('/^www\./', '', $host) ?: '';
    }

    private function deriveEmpresaFromHost(string $host): string
    {
        $host = $this->normalizeHost($host);
        if ($host === '' || $host === 'localhost' || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return 'SIS+';
        }

        $firstLabel = explode('.', $host)[0] ?? '';
        $firstLabel = str_replace(['-', '_'], ' ', trim($firstLabel));
        if ($firstLabel === '') {
            return 'SIS+';
        }

        $parts = preg_split('/\s+/', $firstLabel) ?: [];
        $parts = array_map(static function (string $part): string {
            $part = trim($part);
            if ($part === '') {
                return '';
            }
            return mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
        }, $parts);
        $empresa = trim(implode(' ', array_filter($parts)));
        return $empresa !== '' ? $empresa : 'SIS+';
    }
}


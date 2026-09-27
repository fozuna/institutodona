<?php
namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;

/**
 * Pilar de Pessoas - resolução ÚNICA dos filtros de escopo (empresa e
 * cascata Departamento -> Setor -> Função) usada pela Visão Geral e pelas
 * listagens operacionais. Todo valor recebido por query string é validado
 * contra o que o usuário realmente pode ver (whitelist); valor inválido é
 * descartado silenciosamente (nunca vira filtro, nunca vaza outro tenant).
 */
trait PessoasFiltrosTrait
{
    /**
     * @return array{clientes:array,empresaSel:int,empresaIds:int[],empresaOrg:int,org:array,departamentos:array,setores:array,funcoes:array}
     */
    protected function resolverEscopoPessoas(array $get): array
    {
        $clientes = (new ClienteModel())->all();
        $acessiveis = array_map(static fn(array $c): int => (int)$c['id'], $clientes);
        $empresaReq = (int)($get['empresa_id'] ?? 0);
        $empresaSel = ($empresaReq > 0 && in_array($empresaReq, $acessiveis, true)) ? $empresaReq : 0;
        $empresaIds = $empresaSel > 0 ? [$empresaSel] : $acessiveis;

        // Filtros organizacionais só fazem sentido com uma empresa selecionada (ou única acessível).
        $empresaOrg = $empresaSel > 0 ? $empresaSel : (count($acessiveis) === 1 ? $acessiveis[0] : 0);
        $org = [];
        $departamentos = $setores = $funcoes = [];
        if ($empresaOrg > 0) {
            $departamentos = (new DepartamentoModel())->allByCliente($empresaOrg);
            $depId = (int)($get['departamento_id'] ?? 0);
            if ($depId > 0 && in_array($depId, array_map(static fn($d) => (int)$d['id'], $departamentos), true)) {
                $org['departamento_id'] = $depId;
                $setores = (new SetorModel())->activeByDepartamento($depId);
                $setId = (int)($get['setor_id'] ?? 0);
                if ($setId > 0 && in_array($setId, array_map(static fn($s) => (int)$s['id'], $setores), true)) {
                    $org['setor_id'] = $setId;
                    $funcoes = (new FuncaoModel())->activeBySetor($setId, [$empresaOrg]);
                    $funId = (int)($get['funcao_id'] ?? 0);
                    if ($funId > 0 && in_array($funId, array_map(static fn($f) => (int)$f['id'], $funcoes), true)) {
                        $org['funcao_id'] = $funId;
                    }
                }
            }
        }
        return [
            'clientes' => $clientes,
            'empresaSel' => $empresaSel,
            'empresaIds' => $empresaIds,
            'empresaOrg' => $empresaOrg,
            'org' => $org,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
        ];
    }

    /** Data Y-m-d válida ou null. */
    protected function dataValidaPessoas($v): ?string
    {
        return (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 && strtotime($v) !== false) ? $v : null;
    }
}

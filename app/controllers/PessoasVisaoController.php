<?php
namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\BaseController;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaDashboardModel;
use App\Models\SetorModel;

/**
 * Pilar de Pessoas, Sprint 04 - Visão Geral gerencial. Somente leitura
 * (GET); não registra auditoria de visualização. Empresas sempre limitadas
 * ao escopo do usuário (ClienteModel::all() já filtra por tenant).
 */
class PessoasVisaoController extends BaseController
{
    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $clientes = (new ClienteModel())->all();
        $acessiveis = array_map(static fn(array $c): int => (int)$c['id'], $clientes);
        $empresaReq = (int)($_GET['empresa_id'] ?? 0);
        $empresaSel = ($empresaReq > 0 && in_array($empresaReq, $acessiveis, true)) ? $empresaReq : 0;
        $empresaIds = $empresaSel > 0 ? [$empresaSel] : $acessiveis;

        $isDate = static fn($v): bool => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 && strtotime($v) !== false;
        $inicio = $isDate($_GET['inicio'] ?? null) ? (string)$_GET['inicio'] : date('Y-01-01');
        $fim = $isDate($_GET['fim'] ?? null) ? (string)$_GET['fim'] : date('Y-m-d');
        if ($fim < $inicio) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        // Filtros organizacionais só fazem sentido com uma empresa selecionada (ou única acessível).
        $empresaOrg = $empresaSel > 0 ? $empresaSel : (count($acessiveis) === 1 ? $acessiveis[0] : 0);
        $filters = ['inicio' => $inicio, 'fim' => $fim];
        $departamentos = $setores = $funcoes = [];
        if ($empresaOrg > 0) {
            $departamentos = (new DepartamentoModel())->allByCliente($empresaOrg);
            $depId = (int)($_GET['departamento_id'] ?? 0);
            $depIds = array_map(static fn($d) => (int)$d['id'], $departamentos);
            if ($depId > 0 && in_array($depId, $depIds, true)) {
                $filters['departamento_id'] = $depId;
                $setores = (new SetorModel())->activeByDepartamento($depId);
                $setId = (int)($_GET['setor_id'] ?? 0);
                if ($setId > 0 && in_array($setId, array_map(static fn($s) => (int)$s['id'], $setores), true)) {
                    $filters['setor_id'] = $setId;
                    $funcoes = (new FuncaoModel())->activeBySetor($setId, [$empresaOrg]);
                    $funId = (int)($_GET['funcao_id'] ?? 0);
                    if ($funId > 0 && in_array($funId, array_map(static fn($f) => (int)$f['id'], $funcoes), true)) {
                        $filters['funcao_id'] = $funId;
                    }
                }
            }
        }

        $user = $_SESSION['user'] ?? null;
        $this->render('pessoas/visao/index', [
            'pageTitle' => 'Pessoas — Visão Geral',
            'dados' => (new PessoaDashboardModel())->resumo($empresaIds, $filters),
            'clientes' => $clientes,
            'selectedEmpresa' => $empresaSel,
            'filters' => $filters,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
            'links' => [
                'avaliacoes' => AccessControl::canAccessRoute('pessoas/index', 'GET', $user),
                'pdi' => AccessControl::canAccessRoute('pessoas/pdiIndex', 'GET', $user),
            ],
        ]);
    }
}

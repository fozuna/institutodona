<?php
namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\BaseController;
use App\Models\PessoaDashboardModel;

/**
 * Pilar de Pessoas, Sprint 04 - Visão Geral gerencial. Somente leitura
 * (GET); não registra auditoria de visualização. Empresas sempre limitadas
 * ao escopo do usuário (ClienteModel::all() já filtra por tenant).
 */
class PessoasVisaoController extends BaseController
{
    use PessoasFiltrosTrait;

    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $escopo = $this->resolverEscopoPessoas($_GET);
        $empresaIds = $escopo['empresaIds'];

        $inicio = $this->dataValidaPessoas($_GET['inicio'] ?? null) ?? date('Y-01-01');
        $fim = $this->dataValidaPessoas($_GET['fim'] ?? null) ?? date('Y-m-d');
        if ($fim < $inicio) {
            [$inicio, $fim] = [$fim, $inicio];
        }
        $filters = array_merge(['inicio' => $inicio, 'fim' => $fim], $escopo['org']);

        $user = $_SESSION['user'] ?? null;
        $this->render('pessoas/visao/index', [
            'pageTitle' => 'Pessoas — Visão Geral',
            'dados' => (new PessoaDashboardModel())->resumo($empresaIds, $filters),
            'clientes' => $escopo['clientes'],
            'selectedEmpresa' => $escopo['empresaSel'],
            'filters' => $filters,
            'departamentos' => $escopo['departamentos'],
            'setores' => $escopo['setores'],
            'funcoes' => $escopo['funcoes'],
            'links' => [
                'avaliacoes' => AccessControl::canAccessRoute('pessoas/index', 'GET', $user),
                'pdi' => AccessControl::canAccessRoute('pessoas/pdiIndex', 'GET', $user),
                'gaps' => AccessControl::canAccessRoute('pessoas/gaps', 'GET', $user),
                'acoes' => AccessControl::canAccessRoute('pessoas/acoes', 'GET', $user),
                'necessidades' => AccessControl::canAccessRoute('pessoas/necessidades', 'GET', $user),
            ],
        ]);
    }
}

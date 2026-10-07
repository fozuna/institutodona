<?php
namespace App\Controllers;

use App\Core\AccessControl;
use App\Core\BaseController;
use App\Models\ColaboradorModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaOperacionalModel;

/**
 * Pilar de Pessoas, Sprint 05 - listagens operacionais (GAPs, Ações de
 * Melhoria, Necessidades de Treinamento). Somente leitura (GET): não há
 * mutação aqui, portanto não há CSRF nem audit log de visualização. As
 * mutações continuam nas rotas já existentes (gapStatus, acaoConcluir,
 * necessidadeAtender...), que mantêm sua própria auditoria.
 *
 * Todo parâmetro de query string é validado contra whitelist; valor inválido
 * é descartado (nunca é interpolado, nunca amplia o escopo do usuário).
 */
class PessoasOperacionalController extends BaseController
{
    use PessoasFiltrosTrait;

    public const PER_PAGE = 20;

    private PessoaOperacionalModel $model;

    public function __construct()
    {
        $this->model = new PessoaOperacionalModel();
    }

    /**
     * Filtros comuns às três listagens: escopo (empresa/org), colaborador
     * (validado por tenant + empresa), busca por nome, período e página.
     */
    private function filtrosBase(): array
    {
        $escopo = $this->resolverEscopoPessoas($_GET);
        $f = $escopo['org'];
        $qs = [];
        if ($escopo['empresaSel'] > 0) {
            $qs['empresa_id'] = $escopo['empresaSel'];
        }
        foreach (['departamento_id', 'setor_id', 'funcao_id'] as $k) {
            if (!empty($f[$k])) {
                $qs[$k] = $f[$k];
            }
        }

        $colaborador = null;
        $colId = (int)($_GET['colaborador_id'] ?? 0);
        if ($colId > 0) {
            $c = (new ColaboradorModel())->find($colId); // já escopado por tenant
            if ($c && in_array((int)$c['cliente_id'], $escopo['empresaIds'], true) && $this->canAccessCliente((int)$c['cliente_id'])) {
                $colaborador = $c;
                $f['colaborador_id'] = (int)$c['id'];
                $qs['colaborador_id'] = (int)$c['id'];
            }
        }
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
        if ($q !== '') {
            $f['q'] = $q;
            $qs['q'] = $q;
        }
        $inicio = $this->dataValidaPessoas($_GET['inicio'] ?? null);
        $fim = $this->dataValidaPessoas($_GET['fim'] ?? null);
        if ($inicio !== null && $fim !== null && $fim < $inicio) {
            [$inicio, $fim] = [$fim, $inicio];
        }
        if ($inicio !== null) {
            $f['inicio'] = $inicio;
            $qs['inicio'] = $inicio;
        }
        if ($fim !== null) {
            $f['fim'] = $fim;
            $qs['fim'] = $fim;
        }
        return [
            'escopo' => $escopo,
            'f' => $f,
            'qs' => $qs,
            'colaborador' => $colaborador,
            'page' => max(1, (int)($_GET['page'] ?? 1)),
        ];
    }

    /** Aceita somente valores da whitelist; qualquer outro vira ''. */
    private static function whitelist(string $key, array $permitidos): string
    {
        $v = (string)($_GET[$key] ?? '');
        return in_array($v, $permitidos, true) ? $v : '';
    }

    private function linksExternos(): array
    {
        $user = $_SESSION['user'] ?? null;
        return [
            'plano' => AccessControl::canAccessRoute('planoacao/show', 'GET', $user),
            'treinamento' => AccessControl::canAccessRoute('treinamentos/show', 'GET', $user),
        ];
    }

    private function renderLista(string $view, string $titulo, array $base, array $res, array $extra): void
    {
        $this->render($view, array_merge([
            'pageTitle' => $titulo,
            'items' => $res['items'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => self::PER_PAGE,
            'qs' => $base['qs'],
            'f' => $base['f'],
            'colaborador' => $base['colaborador'],
            'clientes' => $base['escopo']['clientes'],
            'selectedEmpresa' => $base['escopo']['empresaSel'],
            'departamentos' => $base['escopo']['departamentos'],
            'setores' => $base['escopo']['setores'],
            'funcoes' => $base['escopo']['funcoes'],
            'links' => $this->linksExternos(),
        ], $extra));
    }

    public function gaps(): void
    {
        $this->requireClienteAdminAccess();
        $base = $this->filtrosBase();
        $status = self::whitelist('status', PessoaOperacionalModel::GAP_STATUS);
        $tratamento = self::whitelist('tratamento', ['com_acao', 'sem_acao']);
        if ($status !== '') {
            $base['f']['status'] = $status;
            $base['qs']['status'] = $status;
        }
        if ($tratamento !== '') {
            $base['f']['tratamento'] = $tratamento;
            $base['qs']['tratamento'] = $tratamento;
        }
        $res = $this->model->listarGaps($base['escopo']['empresaIds'], $base['f'], $base['page'], self::PER_PAGE);
        $this->renderLista('pessoas/gaps/index', 'Pessoas — GAPs', $base, $res, []);
    }

    public function acoes(): void
    {
        $this->requireClienteAdminAccess();
        $base = $this->filtrosBase();
        $status = self::whitelist('status', PessoaOperacionalModel::ACAO_STATUS);
        if ($status !== '') {
            $base['f']['status'] = $status;
            $base['qs']['status'] = $status;
        }
        foreach (['atrasadas', 'com_plano', 'com_treinamento'] as $flag) {
            if ((string)($_GET[$flag] ?? '') === '1') {
                $base['f'][$flag] = 1;
                $base['qs'][$flag] = 1;
            }
        }
        // Responsável: só aceito se for elegível para a empresa em foco (mesma regra do cadastro de Ações).
        $responsaveis = [];
        $empresaOrg = (int)$base['escopo']['empresaOrg'];
        if ($empresaOrg > 0) {
            $responsaveis = (new PessoaAcaoMelhoriaModel())->usuariosResponsaveisDisponiveis($empresaOrg);
            $respId = (int)($_GET['responsavel_id'] ?? 0);
            if ($respId > 0 && in_array($respId, array_map(static fn(array $u): int => (int)$u['id'], $responsaveis), true)) {
                $base['f']['responsavel_id'] = $respId;
                $base['qs']['responsavel_id'] = $respId;
            }
        }
        $res = $this->model->listarAcoes($base['escopo']['empresaIds'], $base['f'], $base['page'], self::PER_PAGE);
        $this->renderLista('pessoas/acoes/index', 'Pessoas — Ações de Melhoria', $base, $res, ['responsaveis' => $responsaveis]);
    }

    public function feedbacks(): void
    {
        $this->requireClienteAdminAccess();
        $base = $this->filtrosBase();
        $tipo = self::whitelist('tipo', PessoaOperacionalModel::FEEDBACK_TIPOS);
        if ($tipo !== '') {
            $base['f']['tipo'] = $tipo;
            $base['qs']['tipo'] = $tipo;
        }
        $res = $this->model->listarFeedbacks($base['escopo']['empresaIds'], $base['f'], $base['page'], self::PER_PAGE);
        $this->renderLista('pessoas/feedbacks/index', 'Pessoas — Feedbacks', $base, $res, []);
    }

    public function necessidades(): void
    {
        $this->requireClienteAdminAccess();
        $base = $this->filtrosBase();
        $status = self::whitelist('status', PessoaOperacionalModel::NECESSIDADE_STATUS);
        if ($status !== '') {
            $base['f']['status'] = $status;
            $base['qs']['status'] = $status;
        }
        $res = $this->model->listarNecessidades($base['escopo']['empresaIds'], $base['f'], $base['page'], self::PER_PAGE);
        $this->renderLista('pessoas/necessidades/index', 'Pessoas — Necessidades de Treinamento', $base, $res, []);
    }
}

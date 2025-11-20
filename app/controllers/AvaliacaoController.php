<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\AvaliacaoModel;

class AvaliacaoController extends BaseController
{
    private ClienteModel $clientes;
    private AvaliacaoModel $avaliacoes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
        $this->avaliacoes = new AvaliacaoModel();
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $items = $this->clientes->all();
        $this->render('avaliacao/create', ['clientes' => $items]);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }

        $mode = $_POST['cliente_mode'] ?? 'existing';
        $idCliente = null;
        if ($mode === 'existing') {
            $idCliente = (int)($_POST['cliente_id'] ?? 0) ?: null;
        } else {
            $nome = trim($_POST['novo_nome_empresa'] ?? '');
            $cnpj = trim($_POST['novo_cnpj'] ?? '');
            $contato = trim($_POST['novo_contato'] ?? '');
            if ($nome && $cnpj) {
                $idCliente = $this->clientes->create(['nome_empresa' => $nome, 'CNPJ' => $cnpj, 'contato' => $contato]);
            }
        }

        $fin = array_sum(array_map('intval', $_POST['fin'] ?? []));
        $mer = array_sum(array_map('intval', $_POST['mer'] ?? []));
        $pes = array_sum(array_map('intval', $_POST['pes'] ?? []));
        $pro = array_sum(array_map('intval', $_POST['pro'] ?? []));

        $finp = (int)round(($fin/7)*100);
        $merp = (int)round(($mer/7)*100);
        $pesp = (int)round(($pes/7)*100);
        $prop = (int)round(($pro/7)*100);

        $total = $fin + $mer + $pes + $pro; // 0..28
        $real = (int)round(($total/28)*100);

        $payload = [
            'id_cliente' => $idCliente,
            'financeiro_nota' => $fin,
            'mercado_nota' => $mer,
            'pessoas_nota' => $pes,
            'processo_nota' => $pro,
            'financeiro_realidade' => $finp,
            'mercado_realidade' => $merp,
            'pessoas_realidade' => $pesp,
            'processo_realidade' => $prop,
            'total' => $total,
            'realidade_media' => $real,
            'respostas_json' => json_encode($_POST),
        ];

        $this->avaliacoes->create($payload);
        header('Location: index.php?route=dashboard/index');
    }
}
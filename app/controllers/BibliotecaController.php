<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\AplicacaoModel;
use App\Models\ClienteModel;

class BibliotecaController extends BaseController
{
    private AplicacaoModel $aplicacoes;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->aplicacoes = new AplicacaoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = $this->clientes->all();
        $items = [];
        if ($cliente) {
            // fetch all aplicacoes for cliente and aggregate files
            $apps = $this->aplicacoes->byCliente($cliente);
            foreach ($apps as $a) {
                foreach ($this->aplicacoes->arquivosForAplicacao((int)$a['id']) as $f) {
                    $items[] = [
                        'aplicacao_id' => (int)$a['id'],
                        'pilar' => $a['pilar_nome'],
                        'tarefa' => $a['item_pilar'],
                        'cliente' => $a['cliente_nome'],
                        'nome' => $f['nome_original'],
                        'path' => $f['arquivo_path'],
                        'mime' => $f['mime'],
                        'tamanho' => $f['tamanho'],
                        'uploaded_at' => $f['uploaded_at'],
                    ];
                }
            }
        }
        $this->render('biblioteca/index', compact('clientes','cliente','items'));
    }
}

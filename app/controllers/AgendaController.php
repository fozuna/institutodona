<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\AplicacaoModel;
use App\Models\ClienteModel;

class AgendaController extends BaseController
{
    public function index(): void
    {
        $this->requireLogged();
        $aplicacoes = new AplicacaoModel();
        $items = [];
        if (Auth::isInstituto()) {
            // Instituto vê todas as tarefas com datas, agrupadas por data_prevista
            $items = $this->allWithCliente();
        } elseif (Auth::isCliente()) {
            $clienteId = Auth::user()['id_cliente'] ?? null;
            $items = $clienteId ? $this->byClienteWithNome((int)$clienteId) : [];
        } elseif (Auth::isConsultor()) {
            $items = $this->byConsultorWithCliente();
        }
        $this->render('agenda/index', ['items' => $items]);
    }

    private function allWithCliente(): array
    {
        $hasCons = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $consSelect = $hasCons ? 'cons.nome AS consultor' : 'NULL AS consultor';
        $consJoin = $hasCons ? 'LEFT JOIN consultores cons ON cons.id = a.consultor_id' : '';
        $dateSelect = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, cliente, pilar' : 'ORDER BY cliente, pilar';
        $sql = "SELECT a.id, $dateSelect, a.status, m.item_pilar AS tarefa, p.nome AS pilar,
                       cli.nome_empresa AS cliente, $consSelect
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $consJoin
                $order";
        $stmt = \App\Database\Database::getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    private function byClienteWithNome(int $idCliente): array
    {
        $hasCons = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $consSelect = $hasCons ? 'cons.nome AS consultor' : 'NULL AS consultor';
        $consJoin = $hasCons ? 'LEFT JOIN consultores cons ON cons.id = a.consultor_id' : '';
        $dateSelect = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, pilar' : 'ORDER BY pilar';
        $sql = "SELECT a.id, $dateSelect, a.status, m.item_pilar AS tarefa, p.nome AS pilar,
                       cli.nome_empresa AS cliente, $consSelect
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $consJoin
                WHERE a.id_cliente = :id_cliente
                $order";
        $pdo = \App\Database\Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }

    private function byConsultorWithCliente(): array
    {
        // Assume que o usuário consultor tem um registro correspondente em consultores.email
        $email = Auth::user()['email'] ?? '';
        if (!\App\Database\Database::tableExists('consultores')) return [];
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $pdo = \App\Database\Database::getConnection();
        $cstmt = $pdo->prepare('SELECT id FROM consultores WHERE email = :email');
        $cstmt->execute(['email' => $email]);
        $row = $cstmt->fetch();
        $consultorId = $row['id'] ?? 0;
        if (!$consultorId) return [];
        $dateSelect = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, cliente, pilar' : 'ORDER BY cliente, pilar';
        $sql = "SELECT a.id, $dateSelect, a.status, m.item_pilar AS tarefa, p.nome AS pilar,
                       cli.nome_empresa AS cliente, cons.nome AS consultor
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                LEFT JOIN consultores cons ON cons.id = a.consultor_id
                WHERE a.consultor_id = :consultor_id
                $order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['consultor_id' => $consultorId]);
        return $stmt->fetchAll();
    }
}

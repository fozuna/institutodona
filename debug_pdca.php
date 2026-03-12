<?php
require __DIR__ . '/app/autoload.php';

use App\Models\PlanoAcaoTaskModel;

try {
    echo "Iniciando teste de inserção...\n";
    $model = new PlanoAcaoTaskModel();
    
    // Dados mínimos para teste
    $data = [
        'id_cliente' => 1, // Assumindo cliente 1
        'titulo' => 'Teste Debug ' . time(),
        'descricao' => 'Debug insert',
        'fase' => 'DO',
        'status' => 'Planejado',
        'progresso' => 0
    ];
    
    echo "Tentando inserir:\n";
    print_r($data);
    
    $id = $model->create($data);
    echo "Sucesso! ID inserido: $id\n";
    
} catch (Throwable $e) {
    echo "ERRO FATAL:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

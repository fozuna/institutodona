<?php

require_once __DIR__ . '/../app/autoload.php';

// Mock model that skips DB connection for testing logic only
class TestPlanoAcaoTaskModel extends \App\Models\PlanoAcaoTaskModel {
    public function __construct() {
        // Skip DB connection
    }
}

$model = new TestPlanoAcaoTaskModel();

echo "Running Traffic Light Logic Tests...\n";
echo "====================================\n";

$tests = [
    'Yesterday (Red)' => [
        'prazo' => date('Y-m-d', strtotime('yesterday')),
        'status' => 'A Fazer',
        'expected' => 'red'
    ],
    'Today (Yellow)' => [
        'prazo' => date('Y-m-d'),
        'status' => 'A Fazer',
        'expected' => 'yellow'
    ],
    'Tomorrow (Yellow)' => [
        'prazo' => date('Y-m-d', strtotime('+1 day')),
        'status' => 'A Fazer',
        'expected' => 'yellow'
    ],
    'Day After Tomorrow (Yellow)' => [
        'prazo' => date('Y-m-d', strtotime('+2 days')),
        'status' => 'A Fazer',
        'expected' => 'yellow'
    ],
    'Three Days Later (Green)' => [
        'prazo' => date('Y-m-d', strtotime('+3 days')),
        'status' => 'A Fazer',
        'expected' => 'green'
    ],
    'Completed (Gray)' => [
        'prazo' => date('Y-m-d', strtotime('yesterday')),
        'status' => 'Concluído',
        'expected' => 'gray'
    ],
    'No Date (Gray)' => [
        'prazo' => null,
        'status' => 'A Fazer',
        'expected' => 'gray'
    ]
];

$passed = 0;
$total = count($tests);

foreach ($tests as $name => $data) {
    $result = $model->getPrazoStatus($data['prazo'], $data['status']);
    if ($result === $data['expected']) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name: Expected '{$data['expected']}', got '$result'\n";
    }
}

echo "\nResult: $passed/$total passed.\n";
if ($passed === $total) {
    echo "All tests passed!\n";
    exit(0);
} else {
    echo "Some tests failed.\n";
    exit(1);
}

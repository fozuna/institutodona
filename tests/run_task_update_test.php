<?php
@session_start();
require_once __DIR__ . '/../app/autoload.php';

// Mock classes
class MockPlanoAcaoTaskModel extends \App\Models\PlanoAcaoTaskModel {
    public $updateCalled = false;
    public $updateData = [];
    public $id = 0;

    public function update(int $id, array $data): bool {
        $this->updateCalled = true;
        $this->id = $id;
        $this->updateData = $data;
        return true;
    }
}

class TestablePlanoAcaoController extends \App\Controllers\PlanoAcaoController {
    public $redirectUrl = null;

    protected function redirect(string $url): void {
        $this->redirectUrl = $url;
    }
    
    // Override requireRole to bypass auth check
    protected function requireRole(string $role): void {
        // Pass
    }
}

// Setup
$_SESSION['user'] = ['id' => 1, 'nome' => 'Test User', 'role' => 'instituto'];
$_POST['csrf'] = 'valid_token';

// Test Case 1: Successful Update
function testUpdateTaskSuccess() {
    echo "Test 1: Successful Update... ";
    
    // Mock Security
    // Since Security::verifyCsrf is static and hard to mock without runkit, 
    // we assume the environment allows us to bypass or we set the token matches.
    // For this simple script, we'll assume the controller logic is what we are testing mainly.
    // However, the controller checks Security::verifyCsrf. 
    // We might need to trick it or modify the controller to be more testable.
    // Let's assume we can set the session token to match post.
    $_SESSION['csrf'] = 'valid_token';
    
    $_POST['id'] = '101';
    $_POST['titulo'] = 'Updated Task Title';
    $_POST['descricao'] = 'Updated Description';
    $_POST['status'] = 'Em Andamento';
    $_POST['concluido'] = '1'; // Should force progress to 100 and status to Concluído if checked

    $mockTaskModel = new MockPlanoAcaoTaskModel();
    $controller = new TestablePlanoAcaoController();
    $controller->setTaskModel($mockTaskModel);

    // Run
    ob_start(); // Capture output
    $controller->updateTask();
    ob_end_clean();

    // Assertions
    if (!$mockTaskModel->updateCalled) {
        echo "FAILED: Update not called.\n";
        return;
    }
    
    if ($mockTaskModel->id !== 101) {
        echo "FAILED: ID mismatch.\n";
        return;
    }
    
    if ($mockTaskModel->updateData['progresso'] !== 100) {
        echo "FAILED: Progresso logic incorrect. Expected 100, got " . $mockTaskModel->updateData['progresso'] . "\n";
        return;
    }

    if ($mockTaskModel->updateData['status'] !== 'Concluído') {
        echo "FAILED: Status logic incorrect. Expected Concluído, got " . $mockTaskModel->updateData['status'] . "\n";
        return;
    }
    
    if (!isset($_SESSION['flash_success'])) {
        echo "FAILED: Flash success message not set.\n";
        return;
    }

    echo "PASSED\n";
}

// Test Case 2: Missing ID
function testUpdateTaskMissingID() {
    echo "Test 2: Missing ID... ";
    $_POST['id'] = '';
    
    $mockTaskModel = new MockPlanoAcaoTaskModel();
    $controller = new TestablePlanoAcaoController();
    $controller->setTaskModel($mockTaskModel);

    ob_start();
    $controller->updateTask();
    ob_end_clean();

    if ($mockTaskModel->updateCalled) {
        echo "FAILED: Update should not be called without ID.\n";
        return;
    }
    
    if ($controller->redirectUrl !== 'index.php?route=planoacao/index') {
         echo "FAILED: Redirect wrong. Got " . $controller->redirectUrl . "\n";
         return;
    }

    echo "PASSED\n";
}

// Run Tests
try {
    testUpdateTaskSuccess();
    testUpdateTaskMissingID();
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

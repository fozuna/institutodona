<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\PlanoAcaoController;
use App\Models\PlanoAcaoActionModel;

// ==========================================
// Mocks and Test Doubles
// ==========================================

class MockPlanoAcaoActionModel extends PlanoAcaoActionModel
{
    public $updateCalls = [];
    public $shouldThrowException = false;

    public function __construct()
    {
        // Skip DB connection
    }

    public function update(int $id, array $data): bool
    {
        if ($this->shouldThrowException) {
            throw new \Exception("Simulated Database Error");
        }
        $this->updateCalls[] = ['id' => $id, 'data' => $data];
        return true;
    }
}

class TestablePlanoAcaoController extends PlanoAcaoController
{
    public $redirectUrl;
    
    // Override requireRole to bypass authentication check
    protected function requireRole(string $role): void
    {
        // No-op for testing
    }

    // Override requireLogin to bypass authentication check
    protected function requireLogin(): void
    {
        // No-op for testing
    }

    // Override redirect to capture URL
    protected function redirect(string $url): void
    {
        $this->redirectUrl = $url;
    }

    // Public wrapper to call protected method if needed, but updateAction is public
}

// ==========================================
// Test Runner
// ==========================================

$passed = 0;
$total = 0;

function assertTest($condition, $message) {
    global $passed, $total;
    $total++;
    if ($condition) {
        echo "[PASS] $message\n";
        $passed++;
    } else {
        echo "[FAIL] $message\n";
    }
}

echo "Running Plano Ação Save Flow Tests...\n";
echo "=====================================\n";

// ------------------------------------------
// Test Case 1: Successful Update
// ------------------------------------------
echo "\nTest Case 1: Successful Update\n";

// Reset global state
$_SESSION = ['csrf' => 'valid_token'];
$_POST = [
    'csrf' => 'valid_token',
    'id' => '123',
    'task_id' => '456',
    'titulo' => 'Updated Title',
    'owner' => 'Owner Name',
    'due_date' => '2023-12-31',
    'status' => 'Em Execução'
];

$mockModel = new MockPlanoAcaoActionModel();
$controller = new TestablePlanoAcaoController();
$controller->setActionsModel($mockModel);

// Run the action
$controller->updateAction();

// Verify Model interaction
assertTest(count($mockModel->updateCalls) === 1, "Model update called exactly once");
if (count($mockModel->updateCalls) > 0) {
    $call = $mockModel->updateCalls[0];
    assertTest($call['id'] === 123, "Update called with correct ID");
    assertTest($call['data']['titulo'] === 'Updated Title', "Update called with correct Title");
    assertTest($call['data']['status'] === 'Em Execução', "Update called with correct Status");
}

// Verify Controller response
assertTest(isset($_SESSION['flash_success']), "Flash success message set");
assertTest($_SESSION['flash_success'] === 'Item atualizado com sucesso', "Correct success message");
assertTest(trim($controller->redirectUrl) === 'index.php?route=planoacao/show&id=456', "Redirected to correct URL");


// ------------------------------------------
// Test Case 2: CSRF Failure
// ------------------------------------------
echo "\nTest Case 2: CSRF Failure\n";

// Reset global state
$_SESSION = ['csrf' => 'valid_token'];
$_POST = [
    'csrf' => 'invalid_token', // Invalid token
    'id' => '123'
];

$mockModel = new MockPlanoAcaoActionModel();
$controller = new TestablePlanoAcaoController();
$controller->setActionsModel($mockModel);

// Capture output
ob_start();
$controller->updateAction();
$output = ob_get_clean();

// Verify
assertTest($output === 'CSRF inválido', "Output contains 'CSRF inválido'");
assertTest(count($mockModel->updateCalls) === 0, "Model update should NOT be called");


// ------------------------------------------
// Test Case 3: Database Error Handling
// ------------------------------------------
echo "\nTest Case 3: Database Error Handling\n";

// Reset global state
$_SESSION = ['csrf' => 'valid_token'];
$_POST = [
    'csrf' => 'valid_token',
    'id' => '123',
    'task_id' => '456'
];

$mockModel = new MockPlanoAcaoActionModel();
$mockModel->shouldThrowException = true; // Simulate error
$controller = new TestablePlanoAcaoController();
$controller->setActionsModel($mockModel);

// Run action
$controller->updateAction();

// Verify
assertTest(isset($_SESSION['flash_error']), "Flash error message set");
assertTest(strpos($_SESSION['flash_error'], 'Simulated Database Error') !== false, "Error message contains exception message");
assertTest(trim($controller->redirectUrl) === 'index.php?route=planoacao/show&id=456', "Redirected to show page even on error");


// ==========================================
// Summary
// ==========================================
echo "\n=====================================\n";
echo "Tests Completed: $passed/$total passed.\n";

if ($passed === $total) {
    exit(0);
} else {
    exit(1);
}

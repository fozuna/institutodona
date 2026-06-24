<?php
require_once __DIR__ . '/../autoload.php';

$file = __DIR__ . '/../views/auditorias/edit.php';
$source = file_get_contents($file);

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

assert_true($source !== false, 'Carrega a view de edição de auditorias');
assert_true(str_contains($source, 'data-pergunta-count="${index}"'), 'View mantém contador dedicado para a pergunta');
assert_true(
    !str_contains($source, "questoesContainer.querySelectorAll('[data-pergunta]').forEach((el)=>el.addEventListener('input', ()=>{ questoes[Number(el.getAttribute('data-pergunta'))].pergunta = el.value; syncHidden(); renderQuestoes(); }));"),
    'Handler antigo de pergunta não re-renderiza a lista inteira a cada tecla'
);
assert_true(
    str_contains($source, "questoesContainer.querySelectorAll('[data-pergunta]').forEach((el)=>el.addEventListener('input', ()=>{") &&
    str_contains($source, 'countEl.textContent = `${(el.value || \'\').length}/1000`;') &&
    str_contains($source, 'syncHidden();'),
    'Handler atual de pergunta atualiza estado e contador sem recriar o DOM'
);

echo "auditorias_edit_pergunta_focus_regression_test passed.\n";

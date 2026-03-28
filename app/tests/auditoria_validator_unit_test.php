<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AuditoriaValidator;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$validPayload = [
    'cliente_id' => 1,
    'setor_id' => 1,
    'nome_auditoria' => 'Auditoria Operacional',
    'data_auditoria' => '30/04/2026',
    'questoes' => [
        [
            'responsavel_nome' => 'Fabio',
            'pergunta' => 'A evidência do processo está atualizada?',
            'referencia_esperada' => 'POP-001',
            'processos' => ['Compras', 'Qualidade'],
        ],
    ],
];

$errors = AuditoriaValidator::validateCadastro($validPayload);
if (!empty($errors)) {
    failFast('Cadastro válido não deveria retornar erro');
}
ok('Validação de cadastro válido');

$invalidPayload = $validPayload;
$invalidPayload['nome_auditoria'] = 'AB';
$invalidPayload['questoes'] = [];
$errors = AuditoriaValidator::validateCadastro($invalidPayload);
if (empty($errors['nome_auditoria']) || empty($errors['questoes'])) {
    failFast('Validação de cadastro inválido deveria acusar nome e ausência de questões');
}
ok('Validação de nome e mínimo de questões');

$avaliacoes = [
    ['questao_id' => 1, 'conformidade' => 'conforme', 'observacoes' => 'ok'],
    ['questao_id' => 2, 'conformidade' => 'nao_conforme', 'observacoes' => 'ajustar'],
];
$errors = AuditoriaValidator::validateExecucao($avaliacoes, 2);
if (!empty($errors)) {
    failFast('Execução válida não deveria retornar erro');
}
ok('Validação de execução válida');

$errors = AuditoriaValidator::validateExecucao([['questao_id' => 1, 'conformidade' => '', 'observacoes' => '']], 2);
if (empty($errors)) {
    failFast('Execução incompleta deveria retornar erro');
}
ok('Validação de execução incompleta');

$questoesResponsavelOk = [
    ['responsavel_nome' => 'Fabio Ozuna'],
    ['responsavel_nome' => 'Maria Silva'],
];
$errors = AuditoriaValidator::validateResponsaveisCadastrados($questoesResponsavelOk, function (string $nome): bool {
    return in_array($nome, ['Fabio Ozuna', 'Maria Silva'], true);
});
if (!empty($errors)) {
    failFast('Responsáveis válidos não deveriam retornar erro');
}
ok('Validação de responsáveis válidos');

$questoesResponsavelFail = [
    ['responsavel_nome' => 'Fabio Ozuna'],
    ['responsavel_nome' => 'Nome Inexistente'],
];
$errors = AuditoriaValidator::validateResponsaveisCadastrados($questoesResponsavelFail, function (string $nome): bool {
    return in_array($nome, ['Fabio Ozuna', 'Maria Silva'], true);
});
if (empty($errors['questao_2_responsavel_nome'])) {
    failFast('Responsável inválido deveria retornar erro na questão 2');
}
ok('Validação de responsável inválido');

echo "All auditoria validator unit tests passed.\n";

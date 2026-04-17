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
    failFast('Responsáveis preenchidos não deveriam retornar erro');
}
ok('Validação aceita responsáveis internos');

$questoesResponsavelExterno = [
    ['responsavel_nome' => 'Fabio Ozuna'],
    ['responsavel_nome' => 'Nome Inexistente'],
];
$errors = AuditoriaValidator::validateResponsaveisCadastrados($questoesResponsavelExterno, function (string $nome): bool {
    return in_array($nome, ['Fabio Ozuna', 'Maria Silva'], true);
});
if (!empty($errors)) {
    failFast('Agente externo em texto livre não deveria retornar erro');
}
ok('Validação aceita agente externo em texto livre');

echo "All auditoria validator unit tests passed.\n";

<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AuditoriaValidator;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$payload = [
    'cliente_id' => 1,
    'setor_id' => 1,
    'nome_auditoria' => 'Auditoria Integração',
    'data_auditoria' => '12/05/2026',
    'questoes' => [
        [
            'responsavel_nome' => 'Fabio Ozuna',
            'pergunta' => 'Pergunta válida para integração.',
            'referencia_esperada' => 'REF-001',
            'processos' => [],
        ],
        [
            'responsavel_nome' => 'Nome Inexistente',
            'pergunta' => 'Outra pergunta válida para integração.',
            'referencia_esperada' => 'REF-002',
            'processos' => [],
        ],
    ],
];

$errors = AuditoriaValidator::validateCadastro($payload);
$errors = array_merge($errors, AuditoriaValidator::validateResponsaveisCadastrados($payload['questoes'], function (string $nome): bool {
    return in_array($nome, ['Fabio Ozuna', 'Maria Silva'], true);
}));
if (!empty($errors)) {
    failFast('Fluxo integrado deveria aceitar responsável externo em texto livre');
}
ok('Fluxo integrado aceita responsável externo');

$payload['questoes'][1]['responsavel_nome'] = 'Maria Silva';
$errors = AuditoriaValidator::validateCadastro($payload);
$errors = array_merge($errors, AuditoriaValidator::validateResponsaveisCadastrados($payload['questoes'], function (string $nome): bool {
    return in_array($nome, ['Fabio Ozuna', 'Maria Silva'], true);
}));
if (!empty($errors)) {
    failFast('Fluxo integrado deveria aceitar responsáveis internos cadastrados');
}
ok('Fluxo integrado aceita responsáveis internos');

echo "All auditoria responsavel integration tests passed.\n";

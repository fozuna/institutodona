<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\DecimalParser;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

function assert_parses_to($input, float $expected, string $message): void {
    $got = DecimalParser::parse($input);
    assert_true($got !== null && abs($got - $expected) < 0.0001, $message . ' (obtido: ' . var_export($got, true) . ')');
}

function assert_rejected($input, string $message): void {
    assert_true(DecimalParser::parse($input) === null, $message);
}

// --- Caso 9: virgula como separador decimal ---
assert_parses_to('97,5', 97.5, 'Caso 9: aceita "97,5" (vírgula decimal)');

// --- Caso 10: ponto como separador decimal (bug relatado no item 15) ---
assert_parses_to('97.5', 97.5, 'Caso 10: aceita "97.5" (ponto decimal) — bug relatado no item 15');

// --- Caso 11: milhar com ponto + decimal com vírgula (pt-BR completo) ---
assert_parses_to('1.234,56', 1234.56, 'Caso 11: aceita "1.234,56" (milhar ponto + decimal vírgula)');

// --- Caso 12: milhar sem separador, decimal com ponto ---
assert_parses_to('1234.56', 1234.56, 'Caso 12: aceita "1234.56" (decimal com ponto, sem milhar)');

// --- Caso 13: zero ---
assert_parses_to('0', 0.0, 'Caso 13: aceita "0"');
assert_parses_to('0,0', 0.0, 'Caso 13: aceita "0,0"');

// --- Caso 14: valor negativo ---
assert_parses_to('-10', -10.0, 'Caso 14: aceita "-10" (negativo)');
assert_parses_to('-10,5', -10.5, 'Caso 14: aceita "-10,5" (negativo com decimal)');

// --- Caso 15: entradas inválidas nunca viram zero, retornam null ---
assert_rejected('10,0,0', 'Caso 15: rejeita múltiplas vírgulas ("10,0,0")');
assert_rejected('abc', 'Caso 15: rejeita texto não numérico');
assert_rejected('', 'Caso 15: rejeita string vazia');
assert_rejected(null, 'Caso 15: rejeita null');
assert_rejected('12.34,56', 'Caso 15: rejeita agrupamento de milhar mal formado antes da vírgula ("12.34,56")');
assert_rejected('1..2', 'Caso 15: rejeita pontos consecutivos');
assert_rejected('-', 'Caso 15: rejeita "-" sozinho');
assert_rejected('97.', 'Caso 15: rejeita ponto sem dígitos decimais ("97.")');
assert_rejected('.5', 'Caso 15: rejeita ponto sem dígitos inteiros (".5")');
assert_rejected('1,2,3', 'Caso 15: rejeita múltiplas vírgulas ("1,2,3")');
assert_rejected('12abc', 'Caso 15: rejeita mistura de dígitos e letras');
assert_rejected('R$', 'Caso 15: rejeita "R$" sem valor');

// --- Casos adicionais de desambiguação (documentam a regra explícita) ---
assert_parses_to('1234', 1234.0, 'Números inteiros simples continuam funcionando');
assert_parses_to('12.345.678', 12345678.0, 'Múltiplos pontos são tratados como separador de milhar ("12.345.678")');
assert_parses_to('1.234', 1.234, 'Um único ponto é sempre decimal, mesmo com 3 dígitos após ("1.234" = 1,234, não 1234) — mudança deliberada de regra');
assert_parses_to(' 97,50 ', 97.5, 'Aceita espaços nas bordas');
assert_parses_to('R$ 1.234,56', 1234.56, 'Aceita prefixo "R$ " (valores monetários)');

// --- Precisão: arredonda em 4 casas decimais, sem truncar dígitos legítimos ---
assert_parses_to('12,3456', 12.3456, 'Preserva até 4 casas decimais');
$rounded = DecimalParser::parse('12,34567');
assert_true($rounded !== null && abs($rounded - 12.3457) < 0.0001, 'Arredonda a partir da 5ª casa decimal (round-half-up)');

echo "DecimalParser unit tests passed.\n";

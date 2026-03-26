<?php
namespace App\Core;

class AuditoriaValidator
{
    public static function normalizeDate(?string $value): ?string
    {
        $v = trim((string)$value);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1) {
            $parts = explode('-', $v);
            if (count($parts) === 3 && checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                return $v;
            }
            return null;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m) === 1) {
            if (checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
                return $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            return null;
        }
        return null;
    }

    public static function validateCadastro(array $data): array
    {
        $errors = [];
        $clienteId = (int)($data['cliente_id'] ?? 0);
        $setorId = (int)($data['setor_id'] ?? 0);
        $responsavelId = (int)($data['responsavel_id'] ?? 0);
        $date = self::normalizeDate((string)($data['data_auditoria'] ?? ''));
        $pergunta = trim((string)($data['pergunta'] ?? ''));
        $objetivo = trim((string)($data['objetivo'] ?? ''));
        $referencia = trim((string)($data['referencia_esperada'] ?? ''));

        if ($clienteId <= 0) {
            $errors['cliente_id'] = 'Selecione uma empresa.';
        }
        if ($setorId <= 0) {
            $errors['setor_id'] = 'Selecione um setor.';
        }
        if ($responsavelId <= 0) {
            $errors['responsavel_id'] = 'Selecione um responsável.';
        }
        if ($date === null) {
            $errors['data_auditoria'] = 'Informe uma data válida no formato DD/MM/YYYY.';
        }
        if (mb_strlen($pergunta) < 10 || mb_strlen($pergunta) > 500) {
            $errors['pergunta'] = 'A pergunta deve ter entre 10 e 500 caracteres.';
        }
        if (mb_strlen($objetivo) < 20 || mb_strlen($objetivo) > 2000) {
            $errors['objetivo'] = 'O objetivo deve ter no mínimo 20 caracteres.';
        }
        if ($referencia === '') {
            $errors['referencia_esperada'] = 'Informe a referência esperada.';
        }
        return $errors;
    }

    public static function validateExecucao(array $data): array
    {
        $errors = [];
        $avaliacao = trim((string)($data['avaliacao'] ?? ''));
        $obs = trim((string)($data['obs'] ?? ''));
        if (mb_strlen($avaliacao) < 50) {
            $errors['avaliacao'] = 'A avaliação deve ter no mínimo 50 caracteres.';
        }
        if (mb_strlen($obs) > 1000) {
            $errors['obs'] = 'Observações devem ter no máximo 1000 caracteres.';
        }
        return $errors;
    }
}

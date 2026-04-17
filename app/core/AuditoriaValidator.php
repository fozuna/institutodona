<?php
namespace App\Core;

class AuditoriaValidator
{
    public const NOME_MIN = 5;
    public const NOME_MAX = 180;
    public const PERGUNTA_MIN = 10;
    public const PERGUNTA_MAX = 1000;
    public const REFERENCIA_MAX = 2000;
    public const RESPONSAVEL_MAX = 180;

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
        $nomeAuditoria = trim((string)($data['nome_auditoria'] ?? ''));
        $date = self::normalizeDate((string)($data['data_auditoria'] ?? ''));
        $questoes = self::normalizeQuestoes($data['questoes'] ?? []);

        if ($clienteId <= 0) {
            $errors['cliente_id'] = 'Selecione uma empresa.';
        }
        if ($setorId <= 0) {
            $errors['setor_id'] = 'Selecione um setor.';
        }
        if ($date === null) {
            $errors['data_auditoria'] = 'Informe uma data válida no formato DD/MM/YYYY.';
        }
        $nomeLen = mb_strlen($nomeAuditoria);
        if ($nomeLen < self::NOME_MIN || $nomeLen > self::NOME_MAX) {
            $errors['nome_auditoria'] = 'O nome da auditoria deve ter entre 5 e 180 caracteres.';
        }
        if (count($questoes) === 0) {
            $errors['questoes'] = 'Cadastre pelo menos uma questão para continuar.';
        }
        foreach ($questoes as $idx => $questao) {
            $qIndex = $idx + 1;
            $perguntaLen = mb_strlen((string)$questao['pergunta']);
            $refLen = mb_strlen((string)$questao['referencia_esperada']);
            if ($perguntaLen < self::PERGUNTA_MIN || $perguntaLen > self::PERGUNTA_MAX) {
                $errors['questao_' . $qIndex . '_pergunta'] = "Questão {$qIndex}: pergunta deve ter entre 10 e 1000 caracteres.";
            }
            if ($refLen < 3 || $refLen > self::REFERENCIA_MAX) {
                $errors['questao_' . $qIndex . '_referencia_esperada'] = "Questão {$qIndex}: referência esperada deve ter entre 3 e 2000 caracteres.";
            }
        }
        return $errors;
    }

    public static function validateExecucao(array $avaliacoes, int $questoesEsperadas): array
    {
        $errors = [];
        if ($questoesEsperadas <= 0) {
            $errors['questoes'] = 'A auditoria não possui questões cadastradas.';
            return $errors;
        }
        if (count($avaliacoes) < $questoesEsperadas) {
            $errors['avaliacoes'] = 'Avalie todas as questões antes de finalizar.';
        }
        foreach ($avaliacoes as $idx => $item) {
            $conformidade = (string)($item['conformidade'] ?? '');
            $observacoes = trim((string)($item['observacoes'] ?? ''));
            if (!in_array($conformidade, ['conforme', 'nao_conforme', 'nao_aplica'], true)) {
                $errors['avaliacao_' . ($idx + 1)] = 'Selecione conforme, não conforme ou não se aplica para todas as questões.';
            }
            if (mb_strlen($observacoes) > 2000) {
                $errors['avaliacao_obs_' . ($idx + 1)] = 'Observação por questão deve ter no máximo 2000 caracteres.';
            }
        }
        return $errors;
    }

    public static function validateResponsaveisCadastrados(array $questoes, callable $isValidName): array
    {
        $errors = [];
        foreach ($questoes as $idx => $questao) {
            $nome = trim((string)($questao['responsavel_nome'] ?? ''));
            if ($nome === '') {
                continue;
            }
            if (!(bool)$isValidName($nome)) {
                $qIndex = $idx + 1;
                $errors['questao_' . $qIndex . '_responsavel_nome'] = "Questão {$qIndex}: responsável inválido. Selecione um colaborador cadastrado.";
            }
        }
        return $errors;
    }

    public static function normalizeQuestoes(array|string $input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($input)) {
            return [];
        }
        $questoes = [];
        foreach ($input as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $questoes[] = [
                'id' => (int)($raw['id'] ?? 0),
                'responsavel_nome' => trim((string)($raw['responsavel_nome'] ?? '')),
                'responsavel_ids' => array_values(array_unique(array_filter(array_map('intval', $raw['responsavel_ids'] ?? [])))),
                'responsavel_labels' => array_values(array_filter(array_map('trim', $raw['responsavel_labels'] ?? []))),
                'pergunta' => trim((string)($raw['pergunta'] ?? '')),
                'referencia_esperada' => trim((string)($raw['referencia_esperada'] ?? '')),
                'processos' => self::normalizeProcessos($raw['processos'] ?? []),
            ];
        }
        return $questoes;
    }

    public static function normalizeAvaliacoes(array|string $input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($input)) {
            return [];
        }
        $avaliacoes = [];
        foreach ($input as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $questaoId = (int)($raw['questao_id'] ?? 0);
            if ($questaoId <= 0) {
                continue;
            }
            $avaliacoes[] = [
                'questao_id' => $questaoId,
                'conformidade' => trim((string)($raw['conformidade'] ?? '')),
                'observacoes' => trim((string)($raw['observacoes'] ?? '')),
            ];
        }
        return $avaliacoes;
    }

    private static function normalizeProcessos(array|string $input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : preg_split('/[\r\n,;]+/', $input);
        }
        if (!is_array($input)) {
            return [];
        }
        $processos = [];
        foreach ($input as $item) {
            $value = trim((string)$item);
            if ($value !== '') {
                $processos[] = mb_substr($value, 0, 120);
            }
        }
        return array_values(array_unique($processos));
    }
}

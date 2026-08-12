<?php
namespace App\Models;

class SetorMetricaModel extends BaseModel
{
    // Memoização por processo, no mesmo padrão de AuditoriaModel::$tablesEnsured
    // (AuditoriaModel.php:8). Sem isso, cada chamada a ensure() reexecuta
    // "CREATE TABLE IF NOT EXISTS", e esse DDL causa COMMIT IMPLICITO no
    // MySQL/MariaDB mesmo quando a tabela ja existe - o que quebraria a
    // atomicidade de qualquer transacao aberta pelo chamador (ex.:
    // AuditoriaModel::reabrirAuditoria()). Descoberto e corrigido durante a
    // implementacao do item 10 (Fluxo B - reabertura de auditoria).
    private static bool $ensured = false;

    /**
     * Garante o schema ANTES de abrir uma transacao externa (ex.:
     * AuditoriaModel::finalizarAuditoria()/reabrirAuditoria()). Chamar isso
     * antes de beginTransaction() evita que o primeiro CREATE TABLE IF NOT
     * EXISTS de um processo novo (a memoizacao de self::$ensured so ajuda em
     * chamadas SEGUINTES no mesmo processo) dispare o commit implicito do
     * DDL no meio da transacao do chamador.
     */
    public function ensureSchema(): void
    {
        $this->ensure();
    }

    private function ensure(): void
    {
        if (self::$ensured) {
            return;
        }
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS setor_metricas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setor_id INT NOT NULL,
                ano_mes CHAR(7) NOT NULL,
                total_validas INT NOT NULL DEFAULT 0,
                total_conforme INT NOT NULL DEFAULT 0,
                pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_setor_ano_mes (setor_id, ano_mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::$ensured = true;
        } catch (\PDOException $e) {}
    }

    public function registrarConclusao(int $setorId, array $stats, ?string $data = null): void
    {
        $this->ensure();
        $anoMes = ($data ? substr($data, 0, 7) : date('Y-m'));
        $stmt = $this->db->prepare("INSERT INTO setor_metricas (setor_id, ano_mes, total_validas, total_conforme, pct)
            VALUES (:sid, :am, :tv, :tc, :pct)
            ON DUPLICATE KEY UPDATE
              total_validas = total_validas + VALUES(total_validas),
              total_conforme = total_conforme + VALUES(total_conforme),
              pct = CASE
                WHEN (total_validas + VALUES(total_validas)) > 0
                THEN ROUND((total_conforme + VALUES(total_conforme)) / (total_validas + VALUES(total_validas)) * 100, 2)
                ELSE 0.00 END");
        $stmt->execute([
            'sid' => $setorId,
            'am' => $anoMes,
            'tv' => (int)($stats['validas'] ?? 0),
            'tc' => (int)($stats['conforme'] ?? 0),
            'pct' => (float)($stats['pct'] ?? 0.0),
        ]);
    }

    /**
     * Estorna (subtrai) de setor_metricas exatamente a contribuicao que uma
     * finalizacao de auditoria somou anteriormente via registrarConclusao(),
     * usado pela reabertura de auditoria (item 10 - Fluxo B) para que
     * finalizar -> reabrir -> finalizar de novo nao duplique os totais do
     * setor. Deve ser chamado dentro da MESMA transacao do chamador (a
     * conexao PDO e compartilhada via BaseModel/Database::getConnection()).
     *
     * SELECT ... FOR UPDATE trava a linha para leitura consistente dentro da
     * transacao. Se a linha nao existir, ou se o estorno tornaria algum
     * acumulador negativo (sinal de inconsistencia - ex.: estorno tentado
     * duas vezes, ou mes diferente do que foi de fato incrementado), retorna
     * false SEM aplicar nenhuma mudanca; o chamador deve fazer ROLLBACK da
     * transacao inteira em vez de tentar "corrigir" silenciosamente com zero.
     */
    public function estornarConclusao(int $setorId, string $anoMes, array $stats): bool
    {
        $this->ensure();
        $tv = (int)($stats['validas'] ?? 0);
        $tc = (int)($stats['conforme'] ?? 0);

        $sel = $this->db->prepare('SELECT total_validas, total_conforme FROM setor_metricas WHERE setor_id = :sid AND ano_mes = :am FOR UPDATE');
        $sel->execute(['sid' => $setorId, 'am' => $anoMes]);
        $row = $sel->fetch();
        if (!$row) {
            return false;
        }

        $novoValidas = (int)$row['total_validas'] - $tv;
        $novoConforme = (int)$row['total_conforme'] - $tc;
        if ($novoValidas < 0 || $novoConforme < 0) {
            return false;
        }

        $novoPct = $novoValidas > 0 ? round($novoConforme / $novoValidas * 100, 2) : 0.00;
        $upd = $this->db->prepare('UPDATE setor_metricas SET total_validas = :tv, total_conforme = :tc, pct = :pct WHERE setor_id = :sid AND ano_mes = :am');
        return $upd->execute([
            'tv' => $novoValidas,
            'tc' => $novoConforme,
            'pct' => $novoPct,
            'sid' => $setorId,
            'am' => $anoMes,
        ]);
    }

    /**
     * Transfere a contribuicao de uma auditoria Realizada de um setor para
     * outro (item 10 - Fluxo A: correcao cadastral de departamento/setor sem
     * reabrir a auditoria). Estorna do setor antigo e credita no setor novo
     * dentro da MESMA transacao do chamador, preservando o ano_mes original -
     * o total global (soma de todos os setores) nao muda, so a classificacao
     * da contribuicao. Se o estorno do setor antigo falhar (inconsistencia -
     * ver estornarConclusao()), nao credita nada no setor novo e retorna
     * false; o chamador deve fazer ROLLBACK da transacao inteira.
     */
    public function transferirContribuicao(int $setorAntigoId, int $setorNovoId, string $anoMes, array $stats): bool
    {
        $estornado = $this->estornarConclusao($setorAntigoId, $anoMes, $stats);
        if (!$estornado) {
            return false;
        }
        $this->registrarConclusao($setorNovoId, $stats, $anoMes);
        return true;
    }

    public function series(int $setorId, string $inicio, string $fim): array
    {
        $this->ensure();
        $stmt = $this->db->prepare("SELECT ano_mes, pct, total_validas FROM setor_metricas WHERE setor_id = :sid AND ano_mes BETWEEN :i AND :f ORDER BY ano_mes");
        $stmt->execute(['sid' => $setorId, 'i' => $inicio, 'f' => $fim]);
        return $stmt->fetchAll();
    }
}

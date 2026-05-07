<?php
namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $cfg = __DIR__ . '/../../config/config.php';
        if (!file_exists($cfg)) {
            $cfg = __DIR__ . '/../../config/config.example.php';
        }
        $config = require $cfg;
        $db = $config['db'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['dbname'], $db['charset']);

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
            }
            $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
        } catch (PDOException $e) {
            // Em produção, logue o erro e mostre mensagem genérica
            die('Erro ao conectar ao banco de dados.');
        }

        self::$instance = $pdo;
        return self::$instance;
    }

    public static function tableExists(string $table): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function columnExists(string $table, string $column): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function columnType(string $table, string $column): ?string
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare('SELECT column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c LIMIT 1');
        $stmt->execute(['t' => $table, 'c' => $column]);
        $value = $stmt->fetchColumn();
        return is_string($value) && $value !== '' ? $value : null;
    }
}

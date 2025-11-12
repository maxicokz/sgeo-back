<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Initialize database configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get PDO instance (Singleton pattern)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $driver = self::$config['driver'] ?? 'mysql';
                $host = self::$config['host'];

                // For MySQL, convert localhost to 127.0.0.1 to force TCP/IP connection
                // This prevents socket file issues on different systems
                if ($driver === 'mysql' && $host === 'localhost') {
                    $host = '127.0.0.1';
                }

                if ($driver === 'mysql') {
                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                        $host,
                        self::$config['port'],
                        self::$config['database'],
                        self::$config['charset'] ?? 'utf8mb4'
                    );
                } else {
                    // PostgreSQL
                    $dsn = sprintf(
                        'pgsql:host=%s;port=%s;dbname=%s',
                        $host,
                        self::$config['port'],
                        self::$config['database']
                    );
                }

                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    self::$config['options']
                );
            } catch (PDOException $e) {
                $errorMsg = 'Database connection failed: ' . $e->getMessage();

                // Add helpful error messages for common issues
                if (strpos($e->getMessage(), 'No such file or directory') !== false) {
                    $errorMsg .= "\nHint: Check DB_HOST and DB_PORT in .env file. Use 127.0.0.1 instead of localhost for MySQL.";
                } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                    $errorMsg .= "\nHint: Check DB_USER and DB_PASSWORD in .env file.";
                } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                    $errorMsg .= "\nHint: Database '{$self::$config['database']}' does not exist. Create it first.";
                }

                error_log($errorMsg);
                throw new \RuntimeException($errorMsg);
            }
        }

        return self::$instance;
    }

    /**
     * Execute a query and return results
     */
    public static function query(string $sql, array $params = []): array
    {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return single row
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute an insert/update/delete query
     */
    public static function execute(string $sql, array $params = []): bool
    {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Insert data and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');

        $driver = self::$config['driver'] ?? 'mysql';

        if ($driver === 'mysql') {
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $db = self::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            return (int)$db->lastInsertId();
        } else {
            // PostgreSQL - use RETURNING
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $db = self::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
            $result = $stmt->fetch();

            return (int)$result['id'];
        }
    }

    /**
     * Update data
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): bool
    {
        $set = [];
        $values = [];

        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );

        $params = array_merge($values, $whereParams);

        return self::execute($sql, $params);
    }

    /**
     * Delete data
     */
    public static function delete(string $table, string $where, array $params = []): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        return self::execute($sql, $params);
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}

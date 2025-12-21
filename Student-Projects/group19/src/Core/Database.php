<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Core Database Wrapper (PDO)
 * High-performance, secure connection management for MariaDB.
 */
class Database
{
    private ?PDO $pdo = null;
    private array $config;

    public function __construct()
    {
        // Load database configuration
        $this->config = require __DIR__ . '/../../config/database.php';
    }

    /**
     * Get the PDO instance (Lazy Loading)
     * * @return PDO
     * @throws RuntimeException
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Establish the connection with optimized PDO attributes.
     */
    private function connect(): void
    {
        $connConfig = $this->config['connections'][$this->config['default']];
        
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $connConfig['host'],
            $connConfig['port'],
            $connConfig['database'],
            $connConfig['charset']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $connConfig['username'],
                $connConfig['password'],
                $connConfig['options']
            );
        } catch (PDOException $e) {
            // Security: Don't leak credentials in the exception message
            throw new RuntimeException("Database Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Helper to execute a prepared statement and return the result.
     * Use this for simple queries to reduce boilerplate.
     * * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Helper for single row fetches.
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Rollback the current transaction.
     */
    public function rollBack(): void
    {
        $this->getConnection()->rollBack();
    }
}
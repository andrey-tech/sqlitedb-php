<?php

/**
 * @author    andrey-tech
 * @copyright 2019-2023 andrey-tech
 * @link      https://github.com/andrey-tech/
 * @license   MIT
 * @version   3.0.0
 */

declare(strict_types=1);

namespace AndreyTech\SQLiteDB;

use PDO;
use PDOException;
use PDOStatement;
use Generator;

use function array_replace;
use function array_combine;
use function array_map;
use function array_keys;
use function array_values;
use function array_walk;
use function array_filter;
use function array_fill;
use function preg_replace;
use function is_numeric;
use function is_array;
use function is_string;
use function is_null;
use function implode;
use function sprintf;
use function range;
use function count;

use const PHP_EOL;

final class SQLiteDB
{
    /**
     * @var boolean
     */
    private $debugMode = false;

    /**
     * @var integer
     */
    private $queryCounter = 0;

    /**
     * @var PDO|null
     */
    private $pdo;

    /**
     * @var array<non-empty-string, PDOStatement>
     *
     */
    private $statements = [];

    /**
     * @var array{database: string, username: string|null, password: string|null}
     */
    private $config = [
        'database' => './db.sqlite',
        'username' => null,
        'password' => null,
    ];

    /**
     * @var array<int, mixed>
     */
    private $options = [
         PDO::ATTR_TIMEOUT => 60,
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    /**
     * @param array{database?: string, username?: string|null, password?: string|null} $config
     * @param array<int, mixed> $options
     */
    public function __construct(array $config = [], array $options = [])
    {
        $this->config = array_replace($this->config, $config);
        $this->options = array_replace($this->options, $options);
    }

    /**
     * @throws SQLiteDBException
     */
    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $dsn = $this->getDSN();
        $this->debug(sprintf('***** CONNECT "%s"', $dsn));

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->options);
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }
    }

    public function disconnect(): void
    {
        $dsn = $this->getDSN();
        $this->debug(sprintf('***** DISCONNECT "%s"', $dsn));
        $this->pdo = null;
    }

    public function getDSN(): string
    {
        return sprintf('sqlite:%s', $this->config['database']);
    }

    /**
     * @return array{database: string, username: string|null, password: string|null}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array<int, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isConnected(): bool
    {
        return isset($this->pdo);
    }

    public function getPDO(): ?PDO
    {
        return $this->pdo;
    }

    public function getDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function setDebugMode(bool $debugMode): void
    {
        $this->debugMode = $debugMode;
    }

    /**
     * @param non-empty-string $statement
     * @param array<int, mixed> $prepareOptions
     * @throws SQLiteDBException
     */
    private function prepareStatement(string $statement, array $prepareOptions = []): PDOStatement
    {
        $this->connect();

        if (isset($this->statements[$statement])) {
            return $this->statements[$statement];
        }

        try {
            /** @psalm-var PDO $this->pdo */
            $this->statements[$statement] = $this->pdo->prepare($statement, $prepareOptions);
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }

        return $this->statements[$statement];
    }

    /**
     * @param non-empty-string $statement
     * @param array<int|string, null|int|float|string|array<int, null|int|float|string>> $values
     * @param array<int, mixed> $prepareOptions
     * @throws SQLiteDBException
     */
    public function doStatement(
        string $statement,
        array $values = [],
        array $prepareOptions = []
    ): PDOStatement {
        $stmtHandle = $this->prepareStatement($statement, $prepareOptions);
        $this->debugStatement($statement, $values);

        if ($this->isAssociativeArray($values)) {
            $values = $this->getNamedValues($values, $statement);
        }

        try {
            $stmtHandle->closeCursor();
            $stmtHandle->execute($values);
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }

        return $stmtHandle;
    }

    /**
     * @param array<int|string, mixed> $values
     * @param non-empty-string $statement
     * @return array<string, mixed>
     */
    private function getNamedValues(array $values, string $statement): array
    {
        // Add ':' to all keys of array $values
        $values = array_combine(
            array_map(
                static function ($key) {
                    return ':' . $key;
                },
                array_keys($values)
            ),
            array_values($values)
        );

        // Remove from array $values all ':keys', unused in string $statement
        if (preg_match_all('/:\w+/', $statement, $matches)) {
            $allowed = $matches[0];
            $values = array_filter(
                $values,
                static function (string $key) use ($allowed) {
                    return in_array($key, $allowed, true);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $values;
    }

    /**
     * @throws SQLiteDBException
     */
    public function beginTransaction(): void
    {
        $this->connect();

        $this->debugStatement('BEGIN TRANSACTION');

        try {
            /** @psalm-var PDO $this->pdo */
            $this->pdo->beginTransaction();
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @throws SQLiteDBException
     */
    public function commitTransaction(): void
    {
        $this->connect();

        $this->debugStatement('COMMIT TRANSACTION');

        try {
            /** @psalm-var PDO $this->pdo */
            $this->pdo->commit();
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @throws SQLiteDBException
     */
    public function rollbackTransaction(): void
    {
        $this->connect();

        $this->debugStatement('ROLLBACK TRANSACTION');

        try {
            /** @psalm-var PDO $this->pdo */
            $this->pdo->rollback();
        } catch (PDOException $exception) {
            throw new SQLiteDBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @return false|string
     * @throws SQLiteDBException
     */
    public function getLastInsertId(?string $idName = null)
    {
        $this->connect();

        /** @psalm-var PDO $this->pdo */
        return $this->pdo->lastInsertId($idName);
    }

    public function fetchAll(PDOStatement $stmt): Generator
    {
        /** @psalm-suppress MixedAssignment */
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    /**
     * Create string like '?, ?, ?' for statement IN (?, ?, ?)
     *
     * @param array<int|string, int|float|string> $inList
     */
    public function createInStatement(array $inList = []): string
    {
        return implode(', ', array_fill(0, count($inList), '?'));
    }

    /**
     * @param non-empty-string $statement
     * @param array<int|string, null|int|float|string|array<int, null|int|float|string>> $values
     */
    private function debugStatement(string $statement, array $values = []): void
    {
        $this->queryCounter++;

        if (!$this->debugMode) {
            return;
        }

        $query = $this->interpolateQuery($statement, $values);
        $query = preg_replace('/[\r\n\s]+/m', ' ', $query);
        $query = trim($query);

        $this->debug(sprintf('***** [%d] %s', $this->queryCounter, $query));
    }

    protected function debug(string $message): void
    {
        if ($this->debugMode) {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that parameter. Useful for debugging.
     * Assumes anonymous parameters from $params are in the same order as specified in $query.
     * @see https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements/1376838
     *
     * @param string $query The sql query with parameter placeholders
     * @param array<int|string, null|int|float|string|array<int, null|int|float|string>> $params
     *        The array of substitution parameters
     *
     * @return string The interpolated query
     */
    private function interpolateQuery(string $query, array $params = []): string
    {
        $keys = [];
        $values = $params;

        foreach ($params as $key => $value) {
            $keys[] = is_string($key) ? sprintf('/:%s/', $key) : '/[?]/';

            if (is_array($value)) {
                $values[$key] = implode("','", $value);
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        array_walk(
            $values,
            /** @param mixed $value */
            static function (&$value): void {
                if (!is_numeric($value) && 'NULL' !== $value) {
                    $value = sprintf("'%s'", (string) $value);
                }
            }
        );

        /** @var array<int|string, int|float|string> $values */
        return preg_replace($keys, $values, $query, 1);
    }

    /**
     * @param mixed $variable
     */
    protected function isAssociativeArray($variable): bool
    {
        if (!is_array($variable)) {
            return false;
        }

        if ($variable === []) {
            return false;
        }

        return array_keys($variable) !== range(0, count($variable) - 1);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}

<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function crc32;
use function is_string;
use function sprintf;

class AdvisoryLockManager
{
    /**
     * @var array<int, bool>
     */
    private array $locks = [];

    public function __construct(
        private ?Connection $connection = null,
    ) {
    }

    public function exists(int|string $lockKey): bool
    {
        $key = $this->hashKey($lockKey);

        if (isset($this->locks[$key])) {
            return $this->locks[$key];
        }

        return false;
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    public function lock(int|string $lockKey): void
    {
        if (!$this->exists($lockKey)) {
            $key = $this->hashKey($lockKey);

            try {
                $this->getConnection()->executeStatement(sprintf('SELECT pg_advisory_lock(%d)', $key));
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
            }

            $this->locks[$key] = true;
        }
    }

    /**
     * Acquires a transaction-level advisory lock that Postgres releases
     * automatically when the current transaction commits or rolls back.
     * Because the lock cannot be released early, it is not tracked by
     * this class and is not reflected by exists() or unlock().
     *
     * @throws RuntimeException when no transaction is active
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    public function xactLock(int|string $lockKey): void
    {
        $connection = $this->getConnection();

        // Outside of a transaction the lock is released as soon as the statement completes
        if (!$connection->isTransactionActive()) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed because no transaction is active.', (string) $lockKey));
        }

        try {
            $connection->executeStatement(sprintf('SELECT pg_advisory_xact_lock(%d)', $this->hashKey($lockKey)));
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
        }
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function unlock(int|string $lockKey): void
    {
        if ($this->exists($lockKey)) {
            $key = $this->hashKey($lockKey);

            try {
                $this->getConnection()->executeStatement(sprintf('SELECT pg_advisory_unlock(%d)', $key));
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('Releasing advisory lock "%s" failed.', (string) $lockKey), previous: $e);
            } finally {
                $this->locks[$key] = false;
            }
        }
    }

    /**
     * @throws RuntimeException when a database connection is not found
     */
    public function getConnection(): Connection
    {
        return $this->connection ?? throw new RuntimeException('Database connection not found.');
    }

    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    private function hashKey(int|string $lockKey): int
    {
        if (is_string($lockKey)) {
            return crc32($lockKey);
        }

        return $lockKey;
    }
}

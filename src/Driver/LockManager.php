<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;
use OneToMany\PostgresBundle\Util\LockKeyUtil;

use function is_int;
use function sprintf;

class LockManager
{
    public function __construct(
        private ?Connection $connection = null,
    ) {
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('The advisory lock is released when the returned AdvisoryLock is destroyed')]
    public function lock(int|string $key): Lock
    {
        try {
            $pid = $this->getConnection()->fetchOne('SELECT pg_backend_pid() FROM pg_advisory_lock(:id)', [
                'id' => LockKeyUtil::hash($key),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed.', (string) $key), previous: $e);
        }

        return $this->createLock($key, $pid);
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('The advisory lock is released when the returned AdvisoryLock is destroyed')]
    public function tryLock(int|string $lockKey): ?Lock
    {
        try {
            $pid = $this->getConnection()->fetchOne('SELECT pg_backend_pid() WHERE pg_try_advisory_lock(:id)', [
                'id' => LockKeyUtil::hash($lockKey),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed.', (string) $lockKey), previous: $e);
        }

        return false !== $pid ? $this->createLock($lockKey, $key, $pid) : null;
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock outside of an active transaction
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    public function xactLock(int|string $lockKey): void
    {
        if (!$this->getConnection()->isTransactionActive()) {
            throw new RuntimeException(sprintf('Acquiring the advisory lock "%s" requires an active transaction.', (string) $lockKey));
        }

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_xact_lock(:key)', [
                'key' => LockKeyUtil::hash($lockKey),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
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

    /**
     * @throws RuntimeException when the backend PID is invalid
     */
    private function createLock(
        int|string $lockKey,
        mixed $pid,
    ): Lock {
        if (!is_int($pid) || $pid <= 0) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed because the backend PID could not be determined.', (string) $lockKey));
        }

        return new Lock($this->getConnection(), (string) $lockKey, $key, $pid);
    }
}

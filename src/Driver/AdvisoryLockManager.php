<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use OneToMany\PostgresBundle\Exception\RuntimeException;
use OneToMany\PostgresBundle\Util\LockKeyUtil;

use function is_bool;
use function is_int;
use function sprintf;

class AdvisoryLockManager
{
    public function __construct(
        private ?Connection $connection = null,
    ) {
    }

    /**
     * Acquires a session level advisory lock, waiting until it is available.
     *
     * Postgres counts session level locks, so acquiring the same key twice
     * in one session requires releasing it twice. Each AdvisoryLock owns
     * exactly one acquisition, so nested locks on the same key are safe.
     *
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('The advisory lock is released when the returned AdvisoryLock is destroyed')]
    public function lock(int|string $lockKey): AdvisoryLock
    {
        $key = LockKeyUtil::hash($lockKey);

        try {
            $pid = $this->getConnection()->fetchOne('SELECT pg_backend_pid(), pg_advisory_lock(:key)', [
                'key' => $key,
            ], [
                'key' => ParameterType::INTEGER,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
        }

        if (!is_int($pid)) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed because the backend pid could not be determined.', (string) $lockKey));
        }

        return new AdvisoryLock($this->getConnection(), $lockKey, $key, $pid);
    }

    /**
     * Attempts to acquire a session level advisory lock without waiting.
     * Returns null if the lock is held by another session.
     *
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('The advisory lock is released when the returned AdvisoryLock is destroyed')]
    public function tryLock(int|string $lockKey): ?AdvisoryLock
    {
        $key = LockKeyUtil::hash($lockKey);

        try {
            $result = $this->getConnection()->fetchNumeric('SELECT pg_backend_pid(), pg_try_advisory_lock(:key)', [
                'key' => $key,
            ], [
                'key' => ParameterType::INTEGER,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
        }

        [$pid, $acquired] = false !== $result ? $result : [null, null];

        if (!is_int($pid) || !is_bool($acquired)) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed because the server returned an invalid response.', (string) $lockKey));
        }

        return $acquired ? new AdvisoryLock($this->getConnection(), $lockKey, $key, $pid) : null;
    }

    /**
     * Acquires a transaction level advisory lock, waiting until it is
     * available. The lock is released automatically when the current
     * transaction is committed or rolled back and cannot be released
     * any earlier, so no AdvisoryLock is returned.
     *
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
            ], [
                'key' => ParameterType::INTEGER,
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
}

<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;
use OneToMany\PostgresBundle\Lock\Lock;
use OneToMany\PostgresBundle\Lock\Key;

use function is_int;
use function sprintf;

class LockManager
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('otherwise the advisory lock will be released immediately')]
    public function lock(int|string|Key $key): Lock
    {
        if (!$key instanceof Key) {
            $key = new Key($key);
        }

        try {
            $pid = $this->connection->fetchOne('SELECT pg_backend_pid() FROM pg_advisory_lock(:id)', [
                'id' => $key->getId(),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed.', $key->getKey()), previous: $e);
        }

        return $this->createLock($key, $pid);
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    #[\NoDiscard('otherwise the lock will be released immediately')]
    public function tryLock(int|string|Key $key): ?Lock
    {
        if (!$key instanceof Key) {
            $key = new Key($key);
        }

        try {
            $pid = $this->connection->fetchOne('SELECT pg_backend_pid() WHERE pg_try_advisory_lock(:id)', [
                'id' => $key->getId(),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed.', $key->getKey()), previous: $e);
        }

        return false !== $pid ? $this->createLock($key, $pid) : null;
    }

    /**
     * @throws RuntimeException when attempting to acquire the advisory transaction lock outside of an active transaction
     * @throws RuntimeException when acquiring the advisory transaction lock fails
     */
    public function xactLock(int|string|Key $key): void
    {
        if (!$key instanceof Key) {
            $key = new Key($key);
        }

        if (!$this->connection->isTransactionActive()) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" requires an active transaction.', $key->getKey()));
        }

        try {
            $this->connection->executeStatement('SELECT pg_advisory_xact_lock(:id)', [
                'id' => $key->getId(),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Acquiring an advisory transaction lock for the key "%s" failed.', $key->getKey()), previous: $e);
        }
    }

    /**
     * @throws RuntimeException when the backend PID is invalid
     */
    private function createLock(
        Key $key,
        mixed $pid,
    ): Lock {
        if (!is_int($pid) || $pid <= 0) {
            throw new RuntimeException(sprintf('Acquiring an advisory lock for the key "%s" failed because an invalid backend PID was found.', $key->getKey()));
        }

        return new Lock($this->connection, $key, $pid);
    }
}

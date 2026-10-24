<?php

namespace OneToMany\PostgresBundle\Lock;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

final class Lock
{
    private bool $released = false;

    /**
     * @internal use AdvisoryLockManager::lock() or AdvisoryLockManager::tryLock()
     */
    public function __construct(
        private Connection $connection,
        private Key $key,
        private int $pid,
    ) {
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
        }
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * @throws RuntimeException when querying the lock status fails
     */
    public function isAcquired(): bool
    {
        if (
            $this->isReleased()
            || !$this->isConnected()
        ) {
            return false;
        }

        try {
            $acquired = $this->connection->fetchOne("SELECT true FROM pg_locks WHERE locktype = 'advisory' AND pid = pg_backend_pid() AND pid = :pid AND objid::bigint = :id AND granted = true", [
                'pid' => $this->pid,
                'key' => $this->key->getId(),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Querying the status of the advisory lock "%s" failed.', $this->key->getKey()), previous: $e);
        }

        return true === $acquired;
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function release(): bool
    {
        if ($this->isReleased()) {
            return false;
        }

        $this->released = true;

        if (!$this->isConnected()) {
            return false;
        }

        try {
            $released = $this->connection->fetchOne('SELECT CASE WHEN pg_backend_pid() = :pid THEN pg_advisory_unlock(:id) ELSE false END', [
                'pid' => $this->pid,
                'id' => $this->key->getId(),
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Releasing the advisory lock for the key "%s" failed.', $this->key->getKey()), previous: $e);
        }

        return true === $released;
    }

    private function isConnected(): bool
    {
        return $this->connection->isConnected();
    }
}

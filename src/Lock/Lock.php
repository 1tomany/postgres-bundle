<?php

namespace OneToMany\PostgresBundle\Lock;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

final class Lock
{
    public private(set) bool $released = false;

    /**
     * @internal use AdvisoryLockManager::lock() or AdvisoryLockManager::tryLock()
     */
    public function __construct(
        private readonly Connection $connection,
        public readonly int $id,
        public readonly string $key,
        public readonly int $pid,
    ) {
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws RuntimeException when querying the lock status fails
     */
    public function isAcquired(): bool
    {
        if ($this->released || !$this->isConnected()) {
            return false;
        }

        try {
            $acquired = $this->connection->fetchOne("SELECT 1 FROM pg_locks WHERE locktype = 'advisory' AND pid = pg_backend_pid() AND pid = :pid AND objid = :key AND granted", [
                'pid' => $this->pid,
                'key' => $this->id,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Querying the status of the advisory lock "%s" failed.', $this->key), previous: $e);
        }

        return true === $acquired;
    }

    /**
     * Releases the lock. Returns true if the lock was released by this call,
     * or false if it was already released or its session no longer exists.
     *
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function release(): bool
    {
        if ($this->released) {
            return false;
        }

        // Mark the lock as released first so a failure below is not
        // retried by the destructor; a failed query almost always means
        // the session, and therefore the lock, no longer exists anyway
        $this->released = true;

        // A closed connection means the session that held the lock ended,
        // and querying now would only open a new session to a new backend
        if (!$this->connection->isConnected()) {
            return false;
        }

        // Only unlock when still connected to the backend that acquired
        // the lock; after a reconnect it belongs to a session that is gone
        $sql = 'SELECT CASE WHEN pg_backend_pid() = :pid THEN pg_advisory_unlock(:key) ELSE false END';

        try {
            $released = $this->connection->fetchOne($sql, [
                'pid' => $this->pid,
                'key' => $this->id,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Releasing advisory lock "%s" failed.', $this->key), previous: $e);
        }

        return true === $released;
    }

    private function isConnected(): bool
    {
        return $this->connection->isConnected();
    }
}

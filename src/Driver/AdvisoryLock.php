<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

/**
 * A session level advisory lock acquired by the AdvisoryLockManager.
 *
 * The lock is bound to the Postgres backend (identified by its pid) that
 * acquired it. If the connection is closed or reconnects to a different
 * backend, the original session and its locks are gone, so the lock is
 * considered released without issuing another query against the server.
 *
 * Locks should be released explicitly with release(); the destructor is
 * only a last resort for code paths that fail to release the lock.
 */
final class AdvisoryLock
{
    public private(set) bool $released = false;

    /**
     * @internal use AdvisoryLockManager::lock() or AdvisoryLockManager::tryLock()
     */
    public function __construct(
        private readonly Connection $connection,
        public readonly int|string $name,
        public readonly int $key,
        public readonly int $pid,
    ) {
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
            // Destructors can run while another exception is propagating
            // or during shutdown, so a failure here must never be thrown
        }
    }

    /**
     * Returns true if the lock is still held by the backend that acquired it.
     *
     * @throws RuntimeException when querying the lock status fails
     */
    public function isAcquired(): bool
    {
        if ($this->released || !$this->connection->isConnected()) {
            return false;
        }

        $sql = <<<'SQL'
            SELECT EXISTS (
                SELECT 1
                FROM pg_locks
                WHERE locktype = 'advisory'
                    AND pid = pg_backend_pid()
                    AND pid = :pid
                    AND objid = :key
                    AND granted
            )
            SQL;

        try {
            $acquired = $this->connection->fetchOne($sql, [
                'pid' => $this->pid,
                'key' => $this->key,
            ], [
                'pid' => ParameterType::INTEGER,
                'key' => ParameterType::INTEGER,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Querying the status of advisory lock "%s" failed.', (string) $this->name), previous: $e);
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
                'key' => $this->key,
            ], [
                'pid' => ParameterType::INTEGER,
                'key' => ParameterType::INTEGER,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Releasing advisory lock "%s" failed.', (string) $this->name), previous: $e);
        }

        return true === $released;
    }
}

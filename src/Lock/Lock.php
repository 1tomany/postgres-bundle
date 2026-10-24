<?php

namespace OneToMany\PostgresBundle\Lock;

use Doctrine\DBAL\Connection;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

final class Lock
{
    private bool $released = false;

    /**
     * @internal {@see OneToMany\PostgresBundle\Driver\LockManager}
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

    /**
     * @throws RuntimeException when querying the status of the lock fails
     */
    public function isAcquired(): bool
    {
        if ($this->released) {
            return false;
        }

        if ($this->connection->isConnected()) {
            try {
                $acquired = $this->connection->fetchOne("SELECT true FROM pg_locks WHERE locktype = 'advisory' AND pid = pg_backend_pid() AND pid = ? AND objid::bigint = ? AND granted = true", [
                    $this->pid, $this->key->getId(),
                ]);
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('Querying the status of the advisory lock "%s" failed.', $this->key->getKey()), previous: $e);
            }

            return true === $acquired;
        }

        return false;
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function release(): bool
    {
        if ($this->released) {
            return false;
        }

        $this->released = true;

        if ($this->connection->isConnected()) {
            try {
                $released = $this->connection->fetchOne('SELECT CASE WHEN pg_backend_pid() = ? THEN pg_advisory_unlock(?) ELSE false END', [
                    $this->pid, $this->key->getId(),
                ]);
            } catch (\Throwable $e) {
            }

            if (isset($e) || (isset($released) && true !== $released)) {
                throw new RuntimeException(sprintf('Releasing the advisory lock for the key "%s" failed.', $this->key->getKey()), previous: $e ?? null);
            }

            return true;
        }

        return false;
    }
}

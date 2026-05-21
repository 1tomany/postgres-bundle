<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DoctrineExceptionInterface;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function crc32;
use function is_int;
use function is_string;
use function max;
use function sprintf;

class AdvisoryLockManager
{
    public const string LOCK_TYPE = 'advisory';

    public function __construct(
        private ?Connection $connection = null,
    ) {
    }

    public function exists(int|string $lockKey): bool
    {
        $lockCount = 0;

        try {
            $lockCount = $this->getConnection()->fetchOne('SELECT COUNT(*) FROM pg_locks WHERE locktype = ? AND objid = ? AND pid = pg_backend_pid()', [
                self::LOCK_TYPE, $this->createKey($lockKey),
            ]);

            $lockCount = is_int($lockCount) ? max(0, $lockCount) : 0;
        } catch (DoctrineExceptionInterface) {
        }

        return $lockCount > 0;
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    public function lock(int|string $lockKey): void
    {
        $lockKey = $this->createKey($lockKey);

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_lock(?)', [$lockKey]);
        } catch (DoctrineExceptionInterface $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock "%d" failed.', $lockKey), previous: $e);
        }
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function unlock(int|string $lockKey): void
    {
        $lockKey = $this->createKey($lockKey);

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        } catch (DoctrineExceptionInterface $e) {
            throw new RuntimeException(sprintf('Releasing advisory lock "%d" failed.', $lockKey), previous: $e);
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

    private function createKey(int|string $lockKey): int
    {
        if (is_string($lockKey)) {
            return crc32($lockKey);
        }

        return $lockKey;
    }
}

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
        $key = $this->generateKey($lockKey);

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
            $key = $this->generateKey($lockKey);

            try {
                $this->getConnection()->executeStatement(sprintf('SELECT pg_advisory_lock(%d)', $key));
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('Acquiring advisory lock "%s" failed.', (string) $lockKey), previous: $e);
            }

            $this->locks[$key] = true;
        }
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function unlock(int|string $lockKey): void
    {
        if ($this->exists($lockKey)) {
            $key = $this->generateKey($lockKey);

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

    private function generateKey(int|string $lockKey): int
    {
        if (is_string($lockKey)) {
            return crc32($lockKey);
        }

        return $lockKey;
    }
}

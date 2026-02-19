<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalExceptionInterface;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

final readonly class AdvisoryLockManager
{
    public function __construct(private Connection $connection)
    {
    }

    public function lock(int $lockKey): void
    {
        $this->assertLockIdIsPositive($lockKey);
        $this->assertDatabaseConnectionExists();

        try {
            $this->connection->executeStatement('SELECT pg_advisory_lock(?)', [$lockKey]);
        } catch (DbalExceptionInterface $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock %d failed.', $lockKey), previous: $e);
        }
    }

    public function unlock(int $lockKey): void
    {
        $this->assertLockIdIsPositive($lockKey);
        $this->assertDatabaseConnectionExists();

        try {
            $this->connection->executeStatement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        } catch (DbalExceptionInterface $e) {
            throw new RuntimeException(sprintf('Releasing advisory lock %d failed.', $lockKey), previous: $e);
        }
    }

    /**
     * @throws InvalidArgumentException the `$lockKey` is less than one
     */
    private function assertLockIdIsPositive(int $lockKey): void
    {
        if ($lockKey < 1) {
            throw new InvalidArgumentException('The advisory lock key must be greater than zero.');
        }
    }

    /**
     * @throws RuntimeException a database connection does not exist
     */
    private function assertDatabaseConnectionExists(): void
    {
        if (!$this->connection->isConnected()) {
            throw new RuntimeException('The advisory lock failed because a connection to the database does not exist.');
        }
    }
}

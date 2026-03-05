<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalExceptionInterface;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

class AdvisoryLockManager
{
    public function __construct(private ?Connection $connection = null)
    {
    }

    public function lock(int $lockKey): void
    {
        $this->assertLockKeyIsPositive($lockKey);

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_lock(?)', [$lockKey]);
        } catch (DbalExceptionInterface $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock %d failed.', $lockKey), previous: $e);
        }
    }

    public function unlock(int $lockKey): void
    {
        $this->assertLockKeyIsPositive($lockKey);

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_unlock(?)', [$lockKey]);
        } catch (DbalExceptionInterface $e) {
            throw new RuntimeException(sprintf('Releasing advisory lock %d failed.', $lockKey), previous: $e);
        }
    }

    public function getConnection(): Connection
    {
        return $this->connection ?? throw new RuntimeException('Accessing the advisory locker failed because a connection with the database could not be established.');
    }

    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @throws InvalidArgumentException the `$lockKey` is less than one
     */
    private function assertLockKeyIsPositive(int $lockKey): bool
    {
        if ($lockKey < 1) {
            throw new InvalidArgumentException('The advisory lock key must be greater than zero.');
        }

        return true;
    }
}

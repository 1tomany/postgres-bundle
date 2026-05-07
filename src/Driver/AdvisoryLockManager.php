<?php

namespace OneToMany\PostgresBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalExceptionInterface;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

class AdvisoryLockManager
{
    /**
     * @var array<positive-int, true>
     */
    private array $lockKeys = [];

    public function __construct(
        private ?Connection $connection = null,
    )
    {
    }

    /**
     * @throws RuntimeException when acquiring the advisory lock fails
     */
    public function lock(int $lockKey): void
    {
        $this->assertLockKeyIsPositive($lockKey);

        try {
            $this->getConnection()->executeStatement('SELECT pg_advisory_lock(?)', [$lockKey]);
        } catch (DbalExceptionInterface $e) {
            throw new RuntimeException(sprintf('Acquiring advisory lock %d failed.', $lockKey), previous: $e);
        }

        $this->lockKeys[$lockKey] = true;
    }

    /**
     * @throws RuntimeException when releasing the advisory lock fails
     */
    public function unlock(int $lockKey): void
    {
        $this->assertLockKeyIsPositive($lockKey);

        if (\array_key_exists($lockKey, $this->lockKeys)) {
            try {
                $this->getConnection()->executeStatement('SELECT pg_advisory_unlock(?)', [$lockKey]);
            } catch (DbalExceptionInterface $e) {
                throw new RuntimeException(sprintf('Releasing advisory lock %d failed.', $lockKey), previous: $e);
            }

            unset($this->lockKeys[$lockKey]);
        }
    }

    /**
     * @throws RuntimeException when a connection to the database could not be established
     */
    public function getConnection(): Connection
    {
        return $this->connection ?? throw new RuntimeException('A connection with the database could not be established.');
    }

    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @phpstan-assert positive-int $lockKey
     *
     * @throws InvalidArgumentException when the lock key is not a positive integer
     */
    private function assertLockKeyIsPositive(int $lockKey): bool
    {
        if ($lockKey < 1) {
            throw new InvalidArgumentException('The lock key must be a positive integer.');
        }

        return true;
    }
}

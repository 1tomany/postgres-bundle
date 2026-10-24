<?php

namespace OneToMany\PostgresBundle\Driver\Exception;

use OneToMany\PostgresBundle\Exception\RuntimeException;

use function sprintf;

final class AcquiringAdvisoryLockFailedException extends RuntimeException
{
    public function __construct(int|string $lockKey, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Acquiring the advisory lock "%s" failed.', (string) $lockKey), previous: $previous);
    }
}

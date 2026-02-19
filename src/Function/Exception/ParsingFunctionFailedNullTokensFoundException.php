<?php

namespace OneToMany\PostgresBundle\Function\Exception;

use OneToMany\PostgresBundle\Exception\RuntimeException;

final class ParsingFunctionFailedNullTokensFoundException extends RuntimeException
{
    public function __construct(string $function, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The DQL function "%s" could not be parsed because one or more tokens are null.', $function), previous: $previous);
    }
}

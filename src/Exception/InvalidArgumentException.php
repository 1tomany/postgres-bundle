<?php

namespace OneToMany\PostgresBundle\Exception;

use OneToMany\PostgresBundle\Contract\Exception\ExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}

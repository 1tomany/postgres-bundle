<?php

namespace OneToMany\PostgresBundle\Exception;

use OneToMany\PostgresBundle\Contract\Exception\ExceptionInterface;

class DomainException extends \DomainException implements ExceptionInterface
{
}

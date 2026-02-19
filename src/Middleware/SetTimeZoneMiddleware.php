<?php

namespace OneToMany\PostgresBundle\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\ParameterType;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;

final readonly class SetTimeZoneMiddleware implements MiddlewareInterface
{
    public function __construct(private string $timeZone = 'UTC')
    {
        if (!\in_array($this->timeZone, \timezone_identifiers_list())) {
            throw new InvalidArgumentException(\sprintf('The time zone "%s" is not valid.', $this->timeZone));
        }
    }

    public function wrap(Driver $driver): Driver
    {
        $wrappedDriver = new class($driver) extends AbstractDriverMiddleware {
            private string $timeZone = 'UTC';

            public function connect(#[\SensitiveParameter] array $params): Connection
            {
                $connection = parent::connect($params);

                $statement = $connection->prepare('SET timezone = ?');
                $statement->bindValue(1, $this->timeZone, ParameterType::STRING);
                $statement->execute();

                return $connection;
            }

            public function setTimeZone(string $timeZone): static
            {
                $this->timeZone = $timeZone;

                return $this;
            }
        };

        $wrappedDriver->setTimeZone($this->timeZone);

        return $wrappedDriver;
    }
}

<?php

namespace OneToMany\PostgresBundle\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Doctrine\DBAL\ParameterType;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;

use function in_array;
use function sprintf;
use function timezone_identifiers_list;

final readonly class SetTimeZoneMiddleware implements MiddlewareInterface
{
    public function __construct(private string $timeZone = 'UTC')
    {
        if (!in_array($this->timeZone, timezone_identifiers_list())) {
            throw new InvalidArgumentException(sprintf('The time zone "%s" is not valid.', $this->timeZone));
        }
    }

    public function wrap(Driver $driver): Driver
    {
        $wrappedDriver = new class($driver, $this->timeZone) extends AbstractDriverMiddleware {
            public function __construct(Driver $wrappedDriver, private string $timeZone = 'UTC')
            {
                parent::__construct($wrappedDriver);
            }

            public function connect(#[\SensitiveParameter] array $params): Connection
            {
                $connection = parent::connect($params);

                $statement = $connection->prepare('SET timezone = ?');
                $statement->bindValue(1, $this->timeZone, ParameterType::STRING);
                $statement->execute();

                return $connection;
            }
        };

        return $wrappedDriver;
    }
}

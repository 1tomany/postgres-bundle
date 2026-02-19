<?php

namespace OneToMany\PostgresBundle\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\ParameterType;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;

final class SetTimeZoneDriver extends AbstractDriverMiddleware
{
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
        if (!\in_array($timeZone, \timezone_identifiers_list())) {
            throw new InvalidArgumentException(\sprintf('The timezone "%s" is not valid.', $timeZone));
        }

        $this->timeZone = $timeZone;

        return $this;
    }
}

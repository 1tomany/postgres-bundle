<?php

namespace OneToMany\PostgresBundle\Backup;

use Doctrine\DBAL\Connection;

final readonly class BackupConfig
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $binary
     * @param non-empty-string $directory
     * @param list<non-empty-string> $excludeTables
     */
    public function __construct(
        public string $name,
        public string $binary,
        public Connection $connection,
        public string $directory,
        public array $excludeTables,
    ) {
    }
}

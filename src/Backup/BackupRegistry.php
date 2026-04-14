<?php

namespace OneToMany\PostgresBundle\Backup;

use OneToMany\PostgresBundle\Exception\InvalidArgumentException;

use function array_keys;
use function implode;
use function sprintf;

final class BackupRegistry
{
    /**
     * @param array<non-empty-string, BackupConfig> $configs
     */
    public function __construct(
        private array $configs = [],
    ) {
    }

    /**
     * @throws InvalidArgumentException when no backup with the given name is configured
     */
    public function get(string $name): BackupConfig
    {
        if ('' === $name || !isset($this->configs[$name])) {
            $available = implode(', ', array_keys($this->configs)) ?: '<none>';

            throw new InvalidArgumentException(sprintf('No backup named "%s" is configured. Available backups: %s.', $name, $available));
        }

        return $this->configs[$name];
    }

    /**
     * @return list<non-empty-string>
     */
    public function names(): array
    {
        return array_keys($this->configs);
    }
}

<?php

namespace OneToMany\PostgresBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'onetomany:postgres:backup-database',
    description: 'backups a database',
)]
final readonly class BackupDatabaseCommand
{
    public function __invoke(
        SymfonyStyle $io,
    ): int {
        return Command::SUCCESS;
    }

    /*
     * @see Symfony\Component\Console\Command\Command
     */
    /*
    protected function configure(): void
    {
        $this
            ->setName('onetomany:postgres:backup-database')
            ->setDescription('Lists all available models by vendor');
    }
    */
}

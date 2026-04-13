<?php

namespace OneToMany\PostgresBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function array_map;
use function date;
use function escapeshellarg;
use function file_exists;
use function implode;
use function is_dir;
use function is_writable;
use function realpath;
use function sprintf;
use function unlink;
use function vsprintf;

#[AsCommand(
    name: 'onetomany:postgres:backup',
    description: 'backups a database',
)]
final readonly class PostgresBackupCommand
{
    /**
     * @param non-empty-string $dbHost
     * @param non-empty-string $dbUser
     * @param non-empty-string $dbName
     * @param non-empty-string $backupDir
     * @param list<non-empty-string> $excludeTableData
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Postgres server hostname')] string $dbHost,
        #[Argument('Postgres server username')] string $dbUser,
        #[Argument('Postgres database name')] string $dbName,
        #[Argument('Directory to save backup files')] string $backupDir,
        #[Option('Exclude data from these tables')] array $excludeTableData = [],
        #[Option('Overwrite existing backup file')] bool $overwriteBackupFile = false,
    ): int {
        if (!$pgDumpBinary = new ExecutableFinder()->find('pg_dump')) {
            $io->error('The "pg_dump" binary could not be found.');

            return Command::FAILURE;
        }

        if (!$fileDir = realpath($backupDir)) {
            $io->error(sprintf('The backup directory "%s" does not exist.', $backupDir));

            return Command::FAILURE;
        }

        if (!is_dir($fileDir) || !is_writable($fileDir)) {
            $io->error(sprintf("The backup directory \"%s\" is not writable.\n", $fileDir));

            return Command::FAILURE;
        }

        $filePath = sprintf('%s/%s-%s.sql', $fileDir, $dbName, date('Y-m-d_Hi'));

        if (file_exists($filePath)) {
            if ($overwriteBackupFile) {
                unlink($filePath);
            } else {
                $io->error(sprintf('The backup file "%s" already exists.', $filePath));

                return Command::FAILURE;
            }
        }

        $excludeTableDataArguments = array_map(function (string $table): string {
            return sprintf('--exclude-table-data %s', escapeshellarg($table));
        }, $excludeTableData);

        $pgDumpCommand = vsprintf('%s --no-acl --no-owner --host="${:DB_HOST}" --username="${:DB_USER}" --dbname="${:DB_NAME}" --file="${:FILE_PATH}" %s', [
            $pgDumpBinary, implode(' ', $excludeTableDataArguments),
        ]);

        $process = Process::fromShellCommandline($pgDumpCommand);

        $process->mustRun(null, [
            'DB_HOST' => $dbHost,
            'DB_USER' => $dbUser,
            'DB_NAME' => $dbName,
            'FILE_PATH' => $filePath,
        ]);

        return Command::SUCCESS;
    }
}

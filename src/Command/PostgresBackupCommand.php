<?php

namespace OneToMany\PostgresBundle\Command;

use OneToMany\PostgresBundle\Contract\Exception\ExceptionInterface as PostgresExceptionInterface;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function array_map;
use function date;
use function escapeshellarg;
use function implode;
use function is_dir;
use function is_writable;
use function realpath;
use function sprintf;
use function vsprintf;

#[AsCommand(
    name: 'onetomany:postgres:backup',
    description: 'backups a database',
)]
final readonly class PostgresBackupCommand
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

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
        try {
            if (!$pgDumpBinary = new ExecutableFinder()->find('pg_dump')) {
                throw new InvalidArgumentException('The "pg_dump" binary could not be found.');
            }

            if (!$fileDir = realpath($backupDir)) {
                throw new InvalidArgumentException(sprintf('The backup directory "%s" does not exist.', $backupDir));
            }

            if (!is_dir($fileDir) || !is_writable($fileDir)) {
                throw new InvalidArgumentException(sprintf('The backup directory "%s" is not writable.', $fileDir));
            }

            $filePath = sprintf('%s/%s-%s.sql', $fileDir, $dbName, date('Y-m-d_Hi'));

            if ($this->filesystem->exists($filePath)) {
                if ($overwriteBackupFile) {
                    $this->filesystem->remove($filePath);
                } else {
                    throw new InvalidArgumentException(sprintf('The backup file "%s" already exists.', $filePath));
                }
            }
        } catch (PostgresExceptionInterface|FilesystemExceptionInterface $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $excludeTableDataArguments = array_map(function (string $table): string {
            return sprintf('--exclude-table-data %s', escapeshellarg($table));
        }, $excludeTableData);

        $pgDumpCommand = vsprintf('%s --no-acl --no-owner --host=%s --username=%s --dbname=%s --file=%s %s', [
            $pgDumpBinary,
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($filePath),
            implode(' ', $excludeTableDataArguments),
        ]);

        // Generate the
        Process::fromShellCommandline($pgDumpCommand)->mustRun();

        $gzipCommand = vsprintf('gzip -f %s', [
            escapeshellarg($filePath),
        ]);

        Process::fromShellCommandline($gzipCommand)->mustRun();

        return Command::SUCCESS;
    }
}

<?php

namespace OneToMany\PostgresBundle\Command;

use OneToMany\PostgresBundle\Backup\BackupRegistry;
use OneToMany\PostgresBundle\Contract\Exception\ExceptionInterface as PostgresExceptionInterface;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function array_map;
use function date;
use function escapeshellarg;
use function implode;
use function sprintf;
use function trim;
use function vsprintf;

#[AsCommand(
    name: 'onetomany:postgres:backup',
    description: 'Creates a database backup using a named backup configuration',
)]
final class PostgresBackupCommand
{
    use LockableTrait;

    public function __construct(private readonly BackupRegistry $registry)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Name of the backup configuration to run')] string $name,
    ): int {
        if (!$this->lock(sprintf('onetomany_postgres_backup:%s', $name))) {
            $io->writeln('This command is already running in another process.');

            return Command::SUCCESS;
        }

        $filesystem = new Filesystem();

        try {
            $config = $this->registry->get($name);

            if (!$pgDumpBinary = new ExecutableFinder()->find($config->binary)) {
                throw new InvalidArgumentException(sprintf('The "%s" binary could not be found.', $config->binary));
            }

            if (!$backupDir = Path::canonicalize($config->directory)) {
                throw new InvalidArgumentException(sprintf('The backup directory "%s" does not exist.', $config->directory));
            }

            try {
                $filesystem->mkdir($backupDir);
            } catch (FilesystemExceptionInterface $e) {
                throw new InvalidArgumentException(sprintf('The backup directory "%s" is not writable.', $backupDir), previous: $e);
            }

            $params = $config->connection->getParams();

            $this->validateConnectionParameters($params);

            $backupFile = sprintf('%s/%s-%s.sql', $backupDir, $params['dbname'], date('Y-m-d_Hi'));

            if ($filesystem->exists($backupFile)) {
                $filesystem->remove($backupFile);
            }
        } catch (PostgresExceptionInterface|FilesystemExceptionInterface $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $excludeTableArguments = array_map(function (string $table): string {
            return sprintf('--exclude-table-data %s', escapeshellarg($table));
        }, $config->excludeTables);

        $excludeTableArguments = implode(' ', $excludeTableArguments);

        // Dump the Postgres database
        $pgDumpCommand = vsprintf('%s --no-acl --no-owner --host="${:DB_HOST}" --username="${:DB_USER}" --dbname="${:DB_NAME}" --file="${:FILE_PATH}" %s', [
            $pgDumpBinary, $excludeTableArguments,
        ]);

        Process::fromShellCommandline(trim($pgDumpCommand), timeout: 3600)->mustRun(null, [
            'DB_HOST' => $params['host'],
            'DB_USER' => $params['user'],
            'DB_NAME' => $params['dbname'],
            'FILE_PATH' => $backupFile,
        ]);

        // Compress the database backup
        Process::fromShellCommandline('gzip -f "${:FILE_PATH}"', timeout: 900)->mustRun(null, [
            'FILE_PATH' => $backupFile,
        ]);

        $this->release();

        return Command::SUCCESS;
    }

    /**
     * @phpstan-assert array{host: non-empty-string, user: non-empty-string, dbname: non-empty-string} $params
     *
     * @param array{
     *   host?: ?string,
     *   user?: ?string,
     *   dbname?: ?string,
     * } $params
     *
     * @throws InvalidArgumentException if any parameter is missing or empty
     */
    private function validateConnectionParameters(array $params): void
    {
        foreach (['host', 'user', 'dbname'] as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new InvalidArgumentException(sprintf('The connection parameter "%s" is missing or empty.', $param));
            }
        }
    }
}

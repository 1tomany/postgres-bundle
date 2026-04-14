<?php

namespace OneToMany\PostgresBundle;

use OneToMany\PostgresBundle\Backup\BackupConfig;
use OneToMany\PostgresBundle\Backup\BackupRegistry;
use OneToMany\PostgresBundle\Command\PostgresBackupCommand;
use OneToMany\PostgresBundle\Driver\AdvisoryLockManager;
use OneToMany\PostgresBundle\Function\EarthDistance\Boundary;
use OneToMany\PostgresBundle\Function\EarthDistance\Distance;
use OneToMany\PostgresBundle\Middleware\SetTimeZoneMiddleware;
use OneToMany\PostgresBundle\Type\EarthDistance\Earth;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class PostgresBundle extends AbstractBundle
{
    protected string $extensionAlias = 'onetomany_postgres';

    /**
     * @see Symfony\Component\Config\Definition\ConfigurableInterface
     *
     * @param DefinitionConfigurator<'array'> $definition
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
                ->children()
                    ->arrayNode('advisory_lock_manager')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->stringNode('connection')
                                ->cannotBeEmpty()
                                ->defaultValue('database_connection')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('backups')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->stringNode('binary')
                                    ->cannotBeEmpty()
                                    ->defaultValue('pg_dump')
                                ->end()
                                ->stringNode('connection')
                                    ->cannotBeEmpty()
                                    ->defaultValue('database_connection')
                                ->end()
                                ->stringNode('directory')
                                    ->cannotBeEmpty()
                                    ->defaultValue('%kernel.share_dir%/postgres/backups')
                                ->end()
                                ->arrayNode('exclude_tables')
                                    ->stringPrototype()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('middleware')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->stringNode('time_zone')
                                ->cannotBeEmpty()
                                ->defaultValue('UTC')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig('doctrine', [
                'dbal' => [
                    'types' => [
                        'earth' => Earth::class,
                    ],
                ],
                'orm' => [
                    'dql' => [
                        'numeric_functions' => [
                            'BOUNDARY' => Boundary::class,
                            'DISTANCE' => Distance::class,
                        ],
                    ],
                ],
            ]);
        }
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array{
     *   advisory_lock_manager: array{
     *     connection: non-empty-string,
     *   },
     *   backups: array<non-empty-string, array{
     *     binary: non-empty-string,
     *     connection: non-empty-string,
     *     directory: non-empty-string,
     *     exclude_tables: list<non-empty-string>,
     *   }>,
     *   middleware: array{
     *     time_zone: non-empty-string,
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services
            // Drivers
            ->set(AdvisoryLockManager::class)
                ->arg('$connection', service($config['advisory_lock_manager']['connection']))

            // Middlewares
            ->set(SetTimeZoneMiddleware::class)
                ->tag('doctrine.middleware')
                ->arg('$timeZone', $config['middleware']['time_zone'])
        ;

        $backupConfigReferences = [];

        foreach ($config['backups'] as $name => $backup) {
            $serviceId = sprintf('onetomany_postgres.backup_config.%s', $name);

            $services
                ->set($serviceId, BackupConfig::class)
                    ->arg('$name', $name)
                    ->arg('$binary', $backup['binary'])
                    ->arg('$connection', service($backup['connection']))
                    ->arg('$directory', $backup['directory'])
                    ->arg('$excludeTables', $backup['exclude_tables'])
            ;

            $backupConfigReferences[$name] = service($serviceId);
        }

        $services
            ->set(BackupRegistry::class)
                ->arg('$configs', $backupConfigReferences)

            // Commands
            ->set(PostgresBackupCommand::class)
                ->tag('console.command')
                ->arg('$registry', service(BackupRegistry::class))
        ;
    }
}

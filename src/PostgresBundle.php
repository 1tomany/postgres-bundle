<?php

namespace OneToMany\PostgresBundle;

use OneToMany\PostgresBundle\Driver\AdvisoryLockManager;
use OneToMany\PostgresBundle\Function\EarthDistance\Boundary;
use OneToMany\PostgresBundle\Function\EarthDistance\Distance;
use OneToMany\PostgresBundle\Middleware\SetTimeZoneMiddleware;
use OneToMany\PostgresBundle\Type\EarthDistance\Earth;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

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
        $definition->import('../config/config.php');
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
     *   advisory_lock_manager?: array{
     *     connection: non-empty-string,
     *   },
     *   middleware: array{
     *     time_zone: non-empty-string,
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if ($builder->hasDefinition(AdvisoryLockManager::class) && isset($config['advisory_lock_manager'])) {
            $builder
                ->getDefinition(AdvisoryLockManager::class)
                ->setArgument('$connection', new Reference($config['advisory_lock_manager']['connection']));
        }

        if ($builder->hasDefinition(SetTimeZoneMiddleware::class)) {
            $builder
                ->getDefinition(SetTimeZoneMiddleware::class)
                ->setArgument('$timeZone', $config['middleware']['time_zone']);
        }
    }
}

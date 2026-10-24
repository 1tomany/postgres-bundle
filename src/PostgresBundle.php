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
     *   middleware: array{
     *     time_zone: non-empty-string,
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container
            ->services()
                // Drivers
                ->set(AdvisoryLockManager::class)
                    ->arg('$connection', service($config['advisory_lock_manager']['connection']))

                // Middlewares
                ->set(SetTimeZoneMiddleware::class)
                    ->tag('doctrine.middleware')
                    ->arg('$timeZone', $config['middleware']['time_zone'])
        ;
    }
}

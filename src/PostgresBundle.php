<?php

namespace OneToMany\PostgresBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PostgresBundle extends AbstractBundle
{
    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array{
     *   middleware: array{
     *     time_zone: 'UTC',
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $builder->getDefinition('1tomany.postgres_bundle.set_time_zone_middleware')->setArgument('$timeZone', $config['middleware']['time_zone']);
    }
}

<?php

namespace OneToMany\PostgresBundle;

use OneToMany\PostgresBundle\Type\EarthDistance\Earth;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PostgresBundle extends AbstractBundle
{
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
        if (!$builder->hasExtension('doctrine')) {
            return;
        }

        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'earth' => Earth::class,
                ],
            ],
        ]);
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array{
     *   middleware?: array{
     *     time_zone: non-empty-string,
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if (isset($config['middleware'])) {
            $builder
                ->getDefinition('1tomany.postgres_bundle.middleware.set_time_zone')
                ->setArgument('$timeZone', $config['middleware']['time_zone']);
        }
    }
}

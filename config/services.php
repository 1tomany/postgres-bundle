<?php

use OneToMany\PostgresBundle\Driver\AdvisoryLockManager;
use OneToMany\PostgresBundle\Middleware\SetTimeZoneMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            // Drivers
            ->set(AdvisoryLockManager::class)
                ->arg('$connection', service('doctrine.dbal.default_connection'))

            // Middlewares
            ->set(SetTimeZoneMiddleware::class)
                ->tag('doctrine.middleware')
    ;
};

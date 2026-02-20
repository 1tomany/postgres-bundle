<?php

use OneToMany\PostgresBundle\Driver\AdvisoryLockManager;
use OneToMany\PostgresBundle\Middleware\SetTimeZoneMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()

            // Drivers
            ->set('1tomany.postgres_bundle.driver.advisory_lock_manager', AdvisoryLockManager::class)
            ->alias(AdvisoryLockManager::class, service('1tomany.postgres_bundle.driver.advisory_lock_manager'))

            // Middlewares
            ->set('1tomany.postgres_bundle.middleware.set_time_zone', SetTimeZoneMiddleware::class)
                ->tag('doctrine.middleware')
    ;
};

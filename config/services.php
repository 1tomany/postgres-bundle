<?php

use OneToMany\PostgresBundle\Middleware\SetTimeZoneMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set('1tomany.postgres_bundle.set_time_zone_middleware', SetTimeZoneMiddleware::class)
                ->tag('doctrine.middleware')
    ;
};

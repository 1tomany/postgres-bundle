<?php

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @param DefinitionConfigurator<'array'> $configurator
 */
$configurator = static function (DefinitionConfigurator $configurator): void {
    // $middleware = new ArrayNodeDefinition('middleware')
    //     ->children()
    //         ->stringNode('time_zone')
    //             ->cannotBeEmpty()
    //             ->defaultValue('UTC')
    //         ->end()
    //     ->end();

    $configurator
        ->rootNode()
            ->children()
                ->arrayNode('middleware')
                    ->children()
                        ->stringNode('time_zone')
                            ->cannotBeEmpty()
                            ->defaultValue('UTC')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};

return $configurator;

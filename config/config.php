<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @param DefinitionConfigurator<'array'> $configurator
 */
$configurator = static function (DefinitionConfigurator $configurator): void {
    $configurator
        ->rootNode()
            ->children()
                ->arrayNode('advisory_lock_manager')
                    ->children()
                        ->stringNode('connection')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
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

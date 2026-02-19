<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @param DefinitionConfigurator<'array'> $configurator
 */
$configurator = static function (DefinitionConfigurator $configurator): void {
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

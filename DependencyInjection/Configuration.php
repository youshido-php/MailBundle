<?php

namespace Youshido\MailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('youshido_mail');

        $rootNode
            ->children()
                ->arrayNode('config')
                    ->cannotBeEmpty()
                    ->isRequired()
                    ->children()
                        ->variableNode('from')->cannotBeEmpty()->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('cid')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('path')->defaultValue(null)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('letters')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('template')->isRequired()->end()
                            ->scalarNode('subject')->end()
                            ->arrayNode('headers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->scalarNode('value')->defaultValue(null)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

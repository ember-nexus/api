<?php

namespace EmberNexusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const HALF_AN_HOUR_IN_SECONDS = 1800;
    public const THREE_HOURS_IN_SECONDS = 3600 * 3;
    public const THIRTEEN_MONTHS_IN_SECONDS = 3600 * 24 * (365 + 31);
    public const TWO_WEEKS_IN_SECONDS = 3600 * 24 * 14;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ember_nexus');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()

            ->arrayNode('pageSize')
                ->info('Affects how many elements can be returned in collection responses.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('min')
                        ->info('Minimum number of elements which are always returned, if they exist.')
                        ->min(1)
                        ->defaultValue(5)
                    ->end()
                    ->integerNode('default')
                        ->info('Default number of elements which are returned if they exist.')
                        ->min(1)
                        ->defaultValue(25)
                    ->end()
                    ->integerNode('max')
                        ->info('Maximum number of elements which are returned in a single response. Should not be way more than 100, as performance problems may arise.')
                        ->min(1)
                        ->defaultValue(100)
                    ->end()
                ->end()
            ->end()

            ->arrayNode('register')
                ->info('Handles the /register endpoint.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info('If true, the /register endpoint is active and anonymous users can create accounts.')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode('uniqueIdentifier')
                        ->info('The property name of the identifier. Identifier must be unique across the API, usually the email.')
                        ->defaultValue('email')
                    ->end()
                    ->integerNode('uniqueIdentifierRegex')
                        ->info('Either false or a regex for checking the identifier content.')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('instanceConfiguration')
                ->info('Configures the /instance-configuration endpoint')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info('If true, enables the endpoint. If false, 403 error messages are returned.')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode('showVersion')
                        ->info('If false, the version number is omitted.')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('token')
                ->info('Configures the /instance-configuration endpoint')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('minLifetimeInSeconds')
                        ->info('Minimum lifetime of created tokens.')
                        ->defaultValue(self::HALF_AN_HOUR_IN_SECONDS)
                    ->end()
                    ->integerNode('defaultLifetimeInSeconds')
                        ->info('Default lifetime of created tokens.')
                        ->defaultValue(self::THREE_HOURS_IN_SECONDS)
                    ->end()
                    ->scalarNode('maxLifetimeInSeconds')
                        ->info('Maximum lifetime of created tokens. Can be set to false to disable maximum limit.')
                        ->defaultValue(self::THIRTEEN_MONTHS_IN_SECONDS)
                    ->end()
                    ->scalarNode('deleteExpiredTokensAutomaticallyInSeconds')
                        ->info('Expired tokens will be deleted after defined time. Can be set to false to disable auto delete feature.')
                        ->defaultValue(self::TWO_WEEKS_IN_SECONDS)
                    ->end()
                ->end()
            ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}

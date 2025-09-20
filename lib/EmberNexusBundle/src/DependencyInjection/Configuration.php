<?php

declare(strict_types=1);

namespace EmberNexusBundle\DependencyInjection;

use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const int HALF_AN_HOUR_IN_SECONDS = 1800;
    public const int THREE_HOURS_IN_SECONDS = 3600 * 3;
    public const int THIRTEEN_MONTHS_IN_SECONDS = 3600 * 24 * (365 + 31);
    public const int TWO_WEEKS_IN_SECONDS = 3600 * 24 * 14;
    public const int CACHE_ETAG_DEFAULT_UPPER_LIMIT_IN_COLLECTION_ENDPOINTS = 100;
    public const int EXPRESSION_WARNING_LENGTH = 512;
    public const int EXPRESSION_MAX_LENGTH = 10 * 1024;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ember_nexus');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()

            ->arrayNode(EmberNexusConfiguration::PAGE_SIZE)
                ->info('Affects how many elements can be returned in collection responses.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode(EmberNexusConfiguration::PAGE_SIZE_MIN)
                        ->info('Minimum number of elements which are always returned, if they exist.')
                        ->min(1)
                        ->defaultValue(5)
                    ->end()
                    ->integerNode(EmberNexusConfiguration::PAGE_SIZE_DEFAULT)
                        ->info('Default number of elements which are returned if they exist.')
                        ->min(1)
                        ->defaultValue(25)
                    ->end()
                    ->integerNode(EmberNexusConfiguration::PAGE_SIZE_MAX)
                        ->info('Maximum number of elements which are returned in a single response. Should not be way more than 100, as performance problems may arise.')
                        ->min(1)
                        ->defaultValue(100)
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::REGISTER)
                ->info('Handles the /register endpoint.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode(EmberNexusConfiguration::REGISTER_ENABLED)
                        ->info('If true, the /register endpoint is active and anonymous users can create accounts.')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode(EmberNexusConfiguration::REGISTER_UNIQUE_IDENTIFIER)
                        ->info('The property name of the identifier. Identifier must be unique across the API, usually the email.')
                        ->defaultValue('email')
                    ->end()
                    ->scalarNode(EmberNexusConfiguration::REGISTER_UNIQUE_IDENTIFIER_REGEX)
                        ->info('Either false or a regex for checking the identifier content.')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::EXPRESSION)
                ->info('Configures settings related to the execution of expressions')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode(EmberNexusConfiguration::EXPRESSION_ENABLED)
                        ->info('If true, expressions are executed.')
                        ->defaultTrue()
                    ->end()
                    ->integerNode(EmberNexusConfiguration::EXPRESSION_WARNING_LENGTH)
                        ->info('Minimum length for executable expressions to trigger a warning in the logs.')
                        ->min(1)
                        ->defaultValue(self::EXPRESSION_WARNING_LENGTH)
                    ->end()
                    ->integerNode(EmberNexusConfiguration::EXPRESSION_MAX_LENGTH)
                        ->info('Maximum length for executable expressions. Exceeding the limit will throw an exception.')
                        ->min(1)
                        ->defaultValue(self::EXPRESSION_MAX_LENGTH)
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::INSTANCE_CONFIGURATION)
                ->info('Configures the /instance-configuration endpoint.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode(EmberNexusConfiguration::INSTANCE_CONFIGURATION_ENABLED)
                        ->info('If true, enables the endpoint. If false, 403 error messages are returned.')
                        ->defaultTrue()
                    ->end()
                    ->booleanNode(EmberNexusConfiguration::INSTANCE_CONFIGURATION_SHOW_VERSION)
                        ->info('If false, the version number is omitted.')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::TOKEN)
                ->info('Configures token settings.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode(EmberNexusConfiguration::TOKEN_MIN_LIFETIME_IN_SECONDS)
                        ->info('Minimum lifetime of created tokens.')
                        ->defaultValue(self::HALF_AN_HOUR_IN_SECONDS)
                    ->end()
                    ->integerNode(EmberNexusConfiguration::TOKEN_DEFAULT_LIFETIME_IN_SECONDS)
                        ->info('Default lifetime of created tokens.')
                        ->defaultValue(self::THREE_HOURS_IN_SECONDS)
                    ->end()
                    ->scalarNode(EmberNexusConfiguration::TOKEN_MAX_LIFETIME_IN_SECONDS)
                        ->info('Maximum lifetime of created tokens. Can be set to false to disable maximum limit.')
                        ->defaultValue(self::THIRTEEN_MONTHS_IN_SECONDS)
                    ->end()
                    ->scalarNode(EmberNexusConfiguration::TOKEN_DELETE_EXPIRED_TOKENS_AUTOMATICALLY_IN_SECONDS)
                        ->info('Expired tokens will be deleted after defined time. Can be set to false to disable auto delete feature.')
                        ->defaultValue(self::TWO_WEEKS_IN_SECONDS)
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::CACHE)
                ->info('Configures the cache.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode(EmberNexusConfiguration::CACHE_ETAG_SEED)
                        ->info('Seed used to generate Etag values. Changing it invalidates all data cached by clients.')
                        ->defaultValue('')
                    ->end()
                    ->scalarNode(EmberNexusConfiguration::CACHE_ETAG_UPPER_LIMIT_IN_COLLECTION_ENDPOINTS)
                        ->info('Maximum number of items in a collection for which to generate Etags. Bigger collection do not contain Etags due to performance problems.')
                        ->defaultValue(self::CACHE_ETAG_DEFAULT_UPPER_LIMIT_IN_COLLECTION_ENDPOINTS)
                    ->end()
                ->end()
            ->end()

            ->arrayNode(EmberNexusConfiguration::FEATURE_FLAG)
                ->info('Configures feature flags.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode(EmberNexusConfiguration::FEATURE_FLAG_280_OLD_UNIQUE_USER_IDENTIFIER_DISABLED)
                        ->info('If true, the original behavior of some user endpoints in versions before 0.1.6 will no longer be supported. Old behavior will be dropped with version 0.2.0. See GitHub issue #280.')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}

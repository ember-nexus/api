<?php

namespace EmberNexusBundle\DependencyInjection;

use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EmberNexusExtension extends Extension
{
    public function getAlias(): string
    {
        return 'ember_nexus';
    }

    public function load(array $rawConfigurations, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($rawConfigurations, $container);
        $processedConfiguration = $this->processConfiguration($configuration, $rawConfigurations);

        $container->setDefinition(
            EmberNexusConfiguration::class,
            (new Definition())
                ->setFactory([null, 'createFromConfiguration'])
                ->setArguments([$processedConfiguration])
        );
    }
}

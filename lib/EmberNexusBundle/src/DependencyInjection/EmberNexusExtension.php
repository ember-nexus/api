<?php

declare(strict_types=1);

namespace EmberNexusBundle\DependencyInjection;

use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

class EmberNexusExtension extends Extension
{
    public function getAlias(): string
    {
        return 'ember_nexus';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processedConfiguration = $this->processConfiguration(new Configuration(), $configs);

        $container->setDefinition(
            EmberNexusConfiguration::class,
            (new Definition())
                ->setFactory([null, 'createFromConfiguration'])
                ->setArguments([$processedConfiguration])
        );
    }
}

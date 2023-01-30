<?php

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TraceableEventDispatcherDependencyInjection implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has('debug.event_dispatcher')) {
            if ('cli' === PHP_SAPI) {
                // $container->setDefinition('debug.event_dispatcher', new Definition(DeactivatableTraceableEventDispatcher::class));
            }
        }
    }
}

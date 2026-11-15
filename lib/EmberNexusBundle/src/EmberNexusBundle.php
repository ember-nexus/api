<?php

declare(strict_types=1);

namespace EmberNexusBundle;

use EmberNexusBundle\DependencyInjection\EmberNexusExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Kernel\AbstractBundle;

class EmberNexusBundle extends AbstractBundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        if (!($this->extension instanceof ExtensionInterface)) {
            $this->extension = new EmberNexusExtension();
        }

        return $this->extension;
    }
}

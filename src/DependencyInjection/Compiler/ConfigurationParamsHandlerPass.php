<?php

namespace App\DependencyInjection\Compiler;

use App\Service\Facility\ConfigurationParamsHandlerComposite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationParamsHandlerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ConfigurationParamsHandlerComposite::class)) {
            return;
        }

        $definition = $container->findDefinition(ConfigurationParamsHandlerComposite::class);
        $taggedServices = $container->findTaggedServiceIds('configuration_params.handler');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}

<?php

namespace App\DependencyInjection\Compiler;

use App\Service\Routine\RoutineRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RoutineHandlerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(RoutineRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(RoutineRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('fas.routine');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}

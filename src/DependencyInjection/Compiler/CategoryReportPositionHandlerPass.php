<?php

namespace App\DependencyInjection\Compiler;

use App\Service\Report\CategoryReportPositionHandlerComposite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CategoryReportPositionHandlerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CategoryReportPositionHandlerComposite::class)) {
            return;
        }

        $definition = $container->findDefinition(CategoryReportPositionHandlerComposite::class);
        $taggedServices = $container->findTaggedServiceIds('category_report_position.handler');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}

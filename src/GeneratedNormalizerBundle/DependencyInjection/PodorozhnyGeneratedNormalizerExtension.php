<?php

namespace Podorozhny\GeneratedNormalizer\DependencyInjection;

use Podorozhny\GeneratedNormalizer\Serializer\Serializer;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PodorozhnyGeneratedNormalizerExtension extends Extension implements CompilerPassInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $yamlPath = __DIR__ . '/../Resources/config/services';

        $loader = new YamlFileLoader($container, new FileLocator($yamlPath));

        foreach (
            (new Finder())->files()
                ->name('*.yml')
                ->in($yamlPath) as $file
        ) {
            /**
             * @var $file \SplFileInfo
             */

            $loader->load($file->getRealPath());
        }

        $processor     = new Processor();
        $configuration = new Configuration();

        $processor->processConfiguration($configuration, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('serializer')
            ->setClass(Serializer::class)
            ->addArgument(new Reference('podorozhny_generated_normalizer.generator'));
    }
}

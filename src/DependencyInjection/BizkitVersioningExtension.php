<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\DependencyInjection;

use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class BizkitVersioningExtension extends ConfigurableExtension implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $configuredFormat;

    /**
     * @var string
     */
    private $configuredStrategy;

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $this->configuredFormat = $mergedConfig['format'];
        $this->configuredStrategy = $mergedConfig['strategy'];

        $loader = new Loader\XmlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.xml');

        $filepath = $container->getParameterBag()->resolveValue($mergedConfig['filepath']);
        $file = $filepath.\DIRECTORY_SEPARATOR.$mergedConfig['filename'].'.'.$mergedConfig['format'];

        $container->fileExists($file);

        $locator = new FileLocator();
        $loaderResolver = new LoaderResolver([
            new Loader\YamlFileLoader($container, $locator),
            new Loader\XmlFileLoader($container, $locator),
        ]);
        $loaderResolver->resolve($file, $mergedConfig['format'])
            ->import($file, null, 'not_found')
        ;

        $container->registerForAutoconfiguration(StrategyInterface::class)
            ->addTag('bizkit_versioning.strategy')
        ;

        $container->setParameter('bizkit_versioning.parameter_prefix', $mergedConfig['parameter_prefix']);
        $container->setParameter('bizkit_versioning.file', $file);
    }

    /**
     * Needs to happen after {@see ResolveInstanceofConditionalsPass} & {@see ResolveClassPass}.
     */
    public function process(ContainerBuilder $container): void
    {
        $this->registerServiceAlias($container, 'bizkit_versioning.reader', 'format', $this->configuredFormat, ReaderInterface::class);
        $this->registerServiceAlias($container, 'bizkit_versioning.writer', 'format', $this->configuredFormat, WriterInterface::class);

        $this->registerServiceAlias($container, 'bizkit_versioning.strategy', 'alias', $this->configuredStrategy, StrategyInterface::class, true);
    }

    private function registerServiceAlias(
        ContainerBuilder $container,
        string $tag,
        string $attribute,
        string $configuredValue,
        string $alias,
        bool $fallbackToServiceId = false
    ): void {
        $taggedServices = $container->findTaggedServiceIds($tag);

        foreach ($taggedServices as $id => $tags) {
            $value = $tags[0][$attribute] ?? ($fallbackToServiceId ? $id : null);

            if ($configuredValue === $value) {
                $container->setAlias($alias, new Alias($id, false));

                return;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown configuration value "%s", there is no service with the tag "%s" and attribute "%s" with that value registered.',
            $configuredValue,
            $tag,
            $attribute
        ));
    }
}

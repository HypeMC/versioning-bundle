<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\DependencyInjection;

use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
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

    /**
     * @var string|null
     */
    private $configuredVCSHandler;

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $this->configuredFormat = $mergedConfig['format'];
        $this->configuredStrategy = $mergedConfig['strategy'];
        $this->configuredVCSHandler = $mergedConfig['vcs']['handler'];

        $loader = new Loader\XmlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.xml');

        $filepath = $container->getParameterBag()->resolveValue($mergedConfig['filepath']);
        $file = $filepath.\DIRECTORY_SEPARATOR.$mergedConfig['filename'].'.'.$mergedConfig['format'];

        $container->fileExists($file);

        $this->importVersionFile($container, $file, $mergedConfig['format']);

        $container->registerForAutoconfiguration(StrategyInterface::class)
            ->addTag('bizkit_versioning.strategy')
        ;

        $container->registerForAutoconfiguration(VCSHandlerInterface::class)
            ->addTag('bizkit_versioning.vcs_handler')
        ;

        $container->setParameter('bizkit_versioning.parameter_prefix', $mergedConfig['parameter_prefix']);
        $container->setParameter('bizkit_versioning.file', $file);

        $container->setParameter('bizkit_versioning.vcs_commit_message', $mergedConfig['vcs']['commit_message']);
        $container->setParameter('bizkit_versioning.vcs_tag_message', $mergedConfig['vcs']['tag_message']);
        $container->setParameter('bizkit_versioning.vcs_name', $mergedConfig['vcs']['name']);
        $container->setParameter('bizkit_versioning.vcs_email', $mergedConfig['vcs']['email']);
        $container->setParameter('bizkit_versioning.path_to_vcs_executable', $mergedConfig['vcs']['path_to_executable']);
    }

    private function importVersionFile(ContainerBuilder $container, string $file, string $format): void
    {
        $locator = new FileLocator();
        $loaderResolver = new LoaderResolver([
            new Loader\YamlFileLoader($container, $locator),
            new Loader\XmlFileLoader($container, $locator),
        ]);

        /** @var \Symfony\Component\DependencyInjection\Loader\FileLoader|false $loader */
        $loader = $loaderResolver->resolve($file, $format);

        if (false === $loader) {
            throw new InvalidArgumentException(sprintf('Invalid version file format "%s" provided.', $format));
        }

        $loader->import($file, null, 'not_found');
    }

    /**
     * Needs to happen after {@see ResolveInstanceofConditionalsPass} & {@see ResolveClassPass}.
     */
    public function process(ContainerBuilder $container): void
    {
        $this->registerServiceAlias($container, 'bizkit_versioning.reader', 'format', $this->configuredFormat, ReaderInterface::class);
        $this->registerServiceAlias($container, 'bizkit_versioning.writer', 'format', $this->configuredFormat, WriterInterface::class);

        $this->registerServiceAlias($container, 'bizkit_versioning.strategy', 'alias', $this->configuredStrategy, StrategyInterface::class, true);

        if (null !== $this->configuredVCSHandler) {
            $this->registerServiceAlias($container, 'bizkit_versioning.vcs_handler', 'alias', $this->configuredVCSHandler, VCSHandlerInterface::class, true);
        }
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

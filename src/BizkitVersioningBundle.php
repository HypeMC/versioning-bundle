<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle;

use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class BizkitVersioningBundle extends AbstractBundle implements CompilerPassInterface
{
    private string $configuredFormat;
    private string $configuredStrategy;
    private ?string $configuredVCSHandler;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this); // todo
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import(\dirname(__DIR__).'/config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configuredFormat = $config['format'];
        $this->configuredStrategy = $config['strategy'];
        $this->configuredVCSHandler = $config['vcs']['handler'];

        $configurator->import(\dirname(__DIR__).'/config/services.php');

        $filepath = $container->getParameterBag()->resolveValue($config['filepath']);
        $file = $filepath.\DIRECTORY_SEPARATOR.$config['filename'].'.'.$config['format'];

        $container->fileExists($file); // todo

        $configurator->import($file, $config['format'], 'not_found');

        $container->registerForAutoconfiguration(StrategyInterface::class)
            ->addTag('bizkit_versioning.strategy');

        $container->registerForAutoconfiguration(VCSHandlerInterface::class)
            ->addTag('bizkit_versioning.vcs_handler');

        $container->setParameter('.bizkit_versioning.parameter_prefix', $config['parameter_prefix']);
        $container->setParameter('.bizkit_versioning.file', $file);

        $container->setParameter('.bizkit_versioning.vcs_commit_message', $config['vcs']['commit_message']);
        $container->setParameter('.bizkit_versioning.vcs_tag_message', $config['vcs']['tag_message']);
        $container->setParameter('.bizkit_versioning.vcs_tagging_mode', $config['vcs']['tagging_mode']);
        $container->setParameter('.bizkit_versioning.vcs_name', $config['vcs']['name']);
        $container->setParameter('.bizkit_versioning.vcs_email', $config['vcs']['email']);
        $container->setParameter('.bizkit_versioning.path_to_vcs_executable', $config['vcs']['path_to_executable']);
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
        bool $fallbackToServiceId = false,
    ): void {
        $taggedServices = $container->findTaggedServiceIds($tag);

        foreach ($taggedServices as $id => $tags) {
            $value = $tags[0][$attribute] ?? ($fallbackToServiceId ? $id : null);

            if ($configuredValue === $value) {
                $container->setAlias($alias, new Alias($id, false));

                return;
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'Unknown configuration value "%s", there is no service with the tag "%s" and attribute "%s" with that value registered.',
            $configuredValue,
            $tag,
            $attribute,
        ));
    }
}

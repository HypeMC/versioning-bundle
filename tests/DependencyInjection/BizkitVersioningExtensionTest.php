<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\DependencyInjection;

use Bizkit\VersioningBundle\Command\IncrementCommand;
use Bizkit\VersioningBundle\DependencyInjection\BizkitVersioningExtension;
use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Reader\XmlFileReader;
use Bizkit\VersioningBundle\Reader\YamlFileReader;
use Bizkit\VersioningBundle\Strategy\IncrementingStrategy;
use Bizkit\VersioningBundle\Strategy\SemVerStrategy;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\Tests\DependencyInjection\Fixtures\CustomStrategy;
use Bizkit\VersioningBundle\Tests\DependencyInjection\Fixtures\CustomVCSHandler;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\VCS\GitHandler;
use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Bizkit\VersioningBundle\Writer\XmlFileWriter;
use Bizkit\VersioningBundle\Writer\YamlFileWriter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @covers \Bizkit\VersioningBundle\DependencyInjection\BizkitVersioningExtension
 */
final class BizkitVersioningExtensionTest extends TestCase
{
    public function testDefaultServicesAreRegistered(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertTrue($container->has(YamlFileReader::class));
        $this->assertTrue($container->getDefinition(YamlFileReader::class)->hasTag('bizkit_versioning.reader'));

        $this->assertTrue($container->has(YamlFileWriter::class));
        $this->assertTrue($container->getDefinition(YamlFileWriter::class)->hasTag('bizkit_versioning.writer'));

        $this->assertTrue($container->has(XmlFileReader::class));
        $this->assertTrue($container->getDefinition(XmlFileReader::class)->hasTag('bizkit_versioning.reader'));

        $this->assertTrue($container->has(XmlFileWriter::class));
        $this->assertTrue($container->getDefinition(XmlFileWriter::class)->hasTag('bizkit_versioning.writer'));

        $this->assertTrue($container->has(IncrementingStrategy::class));
        $this->assertTrue($container->getDefinition(IncrementingStrategy::class)->hasTag('bizkit_versioning.strategy'));

        $this->assertTrue($container->has(SemVerStrategy::class));
        $this->assertTrue($container->getDefinition(SemVerStrategy::class)->hasTag('bizkit_versioning.strategy'));

        $this->assertTrue($container->has(GitHandler::class));
        $this->assertTrue($container->getDefinition(GitHandler::class)->hasTag('bizkit_versioning.vcs_handler'));

        $this->assertTrue($container->has(IncrementCommand::class));
        $this->assertTrue($container->getDefinition(IncrementCommand::class)->hasTag('console.command'));
    }

    public function testPlaceholderValuesAreResolvedForFilepath(): void
    {
        $config = [
            'filepath' => '%placeholder.dir%',
        ];

        $container = new ContainerBuilder();
        $container->setParameter('placeholder.dir', __DIR__);

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertTrue($container->hasParameter('bizkit_versioning.file'));
        $this->assertSame(__DIR__.'/version.yaml', $container->getParameter('bizkit_versioning.file'));
    }

    public function testVersionFileIsAddedAsResourceIfExists(): void
    {
        $config = [
            'filepath' => __DIR__.'/Fixtures',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $resources = array_map('strval', $container->getResources());
        $this->assertContains(__DIR__.'/Fixtures/version.yaml', $resources);
    }

    public function testVersionFileIsAddedAsResourceIfDoesNotExist(): void
    {
        $config = [
            'filepath' => __DIR__.'/Fixtures',
            'filename' => 'foo',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $resources = array_map('strval', $container->getResources());
        $this->assertContains(__DIR__.'/Fixtures/foo.yaml', $resources);
    }

    public function testVersionParametersAreLoadedFromYamlVersionFile(): void
    {
        $config = [
            'filepath' => __DIR__.'/Fixtures',
            'format' => 'yaml',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertTrue($container->hasParameter('app.version'));
        $this->assertSame('1.2.3', $container->getParameter('app.version'));

        $this->assertTrue($container->hasParameter('app.version_hash'));
        $this->assertSame('b0e8daa258acbb6fc4c86f89e0c9183e', $container->getParameter('app.version_hash'));

        $this->assertTrue($container->hasParameter('app.release_date'));
        $this->assertSame('2020-05-22T11:58:13+02:00', $container->getParameter('app.release_date'));
    }

    public function testVersionParametersAreLoadedFromXmlVersionFile(): void
    {
        $config = [
            'filepath' => __DIR__.'/Fixtures',
            'format' => 'xml',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertTrue($container->hasParameter('app.version'));
        $this->assertSame('3.2.1', $container->getParameter('app.version'));

        $this->assertTrue($container->hasParameter('app.version_hash'));
        $this->assertSame('f2f87b58be0d57ecf71ada8df361a2d9', $container->getParameter('app.version_hash'));

        $this->assertTrue($container->hasParameter('app.release_date'));
        $this->assertSame('2020-02-25T10:46:12+02:00', $container->getParameter('app.release_date'));
    }

    public function testExceptionIsNotThrownWhenVersionFileDoesNotExist(): void
    {
        $config = [
            'filename' => 'foo',
            'filepath' => __DIR__.'/Fixtures',
            'format' => 'yaml',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertFalse($container->hasParameter('app.version'));
        $this->assertFalse($container->hasParameter('app.version_hash'));
        $this->assertFalse($container->hasParameter('app.release_date'));
    }

    public function testStrategiesAreAutomaticallyTagged(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $container->register(CustomStrategy::class)
            ->setAutoconfigured(true)
            ->setPublic(true) // we don't want the service to be removed when we compile the container
        ;

        $container->compile();

        $this->assertTrue($container->has(CustomStrategy::class));
        $this->assertTrue($container->getDefinition(CustomStrategy::class)->hasTag('bizkit_versioning.strategy'));
    }

    public function testVCSHandlersAreAutomaticallyTagged(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $container->register(CustomVCSHandler::class)
            ->setAutoconfigured(true)
            ->setPublic(true) // we don't want the service to be removed when we compile the container
        ;

        $container->compile();

        $this->assertTrue($container->has(CustomVCSHandler::class));
        $this->assertTrue($container->getDefinition(CustomVCSHandler::class)->hasTag('bizkit_versioning.vcs_handler'));
    }

    public function testParametersAreRegistered(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->assertTrue($container->hasParameter('bizkit_versioning.parameter_prefix'));
        $this->assertSame('application', $container->getParameter('bizkit_versioning.parameter_prefix'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.file'));
        $this->assertSame(__DIR__.'/version.yaml', $container->getParameter('bizkit_versioning.file'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.vcs_commit_message'));
        $this->assertNull($container->getParameter('bizkit_versioning.vcs_commit_message'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.vcs_tag_message'));
        $this->assertNull($container->getParameter('bizkit_versioning.vcs_tag_message'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.vcs_name'));
        $this->assertNull($container->getParameter('bizkit_versioning.vcs_name'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.vcs_email'));
        $this->assertNull($container->getParameter('bizkit_versioning.vcs_email'));

        $this->assertTrue($container->hasParameter('bizkit_versioning.path_to_vcs_executable'));
        $this->assertNull($container->getParameter('bizkit_versioning.path_to_vcs_executable'));
    }

    public function testReaderAliasIsRegistered(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);
        $extension->process($container);

        $this->assertTrue($container->hasAlias(ReaderInterface::class));
        $this->assertSame(YamlFileReader::class, (string) $container->getAlias(ReaderInterface::class));
    }

    public function testExceptionIsThrownOnInvalidReaderFormat(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $container->removeDefinition(YamlFileReader::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration value "yaml", there is no service with the tag "bizkit_versioning.reader" and attribute "format" with that value registered.');

        $extension->process($container);
    }

    public function testWriterAliasIsRegistered(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);
        $extension->process($container);

        $this->assertTrue($container->hasAlias(WriterInterface::class));
        $this->assertSame(YamlFileWriter::class, (string) $container->getAlias(WriterInterface::class));
    }

    public function testExceptionIsThrownOnInvalidWriterFormat(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $container->removeDefinition(YamlFileWriter::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration value "yaml", there is no service with the tag "bizkit_versioning.writer" and attribute "format" with that value registered.');

        $extension->process($container);
    }

    public function testStrategyAliasIsRegistered(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);
        $extension->process($container);

        $this->assertTrue($container->hasAlias(StrategyInterface::class));
        $this->assertSame(IncrementingStrategy::class, (string) $container->getAlias(StrategyInterface::class));
    }

    public function testExceptionIsThrownOnInvalidStrategy(): void
    {
        $config = [
            'filepath' => __DIR__,
            'strategy' => 'foo',
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration value "foo", there is no service with the tag "bizkit_versioning.strategy" and attribute "alias" with that value registered.');

        $extension->process($container);
    }

    public function testVCSHandlerAliasIsRegisteredWhenConfigurationIsNotNull(): void
    {
        $config = [
            'filepath' => __DIR__,
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);
        $extension->process($container);

        $this->assertTrue($container->hasAlias(VCSHandlerInterface::class));
        $this->assertSame(GitHandler::class, (string) $container->getAlias(VCSHandlerInterface::class));
    }

    public function testVCSHandlerAliasIsNotRegisteredWhenConfigurationIsNull(): void
    {
        $config = [
            'filepath' => __DIR__,
            'vcs' => [
                'handler' => null,
            ],
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);
        $extension->process($container);

        $this->assertFalse($container->hasAlias(VCSHandlerInterface::class));
    }

    public function testExceptionIsThrownOnInvalidVCSHandler(): void
    {
        $config = [
            'filepath' => __DIR__,
            'vcs' => [
                'handler' => 'foo',
            ],
        ];

        $container = new ContainerBuilder();

        $extension = new BizkitVersioningExtension();
        $extension->load([$config], $container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration value "foo", there is no service with the tag "bizkit_versioning.vcs_handler" and attribute "alias" with that value registered.');

        $extension->process($container);
    }
}

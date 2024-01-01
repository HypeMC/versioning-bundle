<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bizkit\VersioningBundle\Command\IncrementCommand;
use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Reader\XmlFileReader;
use Bizkit\VersioningBundle\Reader\YamlFileReader;
use Bizkit\VersioningBundle\Strategy\IncrementingStrategy;
use Bizkit\VersioningBundle\Strategy\SemVerStrategy;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\VCS\GitHandler;
use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Bizkit\VersioningBundle\Writer\XmlFileWriter;
use Bizkit\VersioningBundle\Writer\YamlFileWriter;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->private()

        ->set(YamlFileReader::class)
            ->args([
                param('.bizkit_versioning.file'),
                param('.bizkit_versioning.parameter_prefix'),
            ])
            ->tag('bizkit_versioning.reader', ['format' => 'yaml'])

        ->set(YamlFileWriter::class)
            ->args([
                param('.bizkit_versioning.file'),
                param('.bizkit_versioning.parameter_prefix'),
            ])
            ->tag('bizkit_versioning.writer', ['format' => 'yaml'])

        ->set(XmlFileReader::class)
            ->args([
                param('.bizkit_versioning.file'),
                param('.bizkit_versioning.parameter_prefix'),
            ])
            ->tag('bizkit_versioning.reader', ['format' => 'xml'])

        ->set(XmlFileWriter::class)
            ->args([
                param('.bizkit_versioning.file'),
                param('.bizkit_versioning.parameter_prefix'),
            ])
            ->tag('bizkit_versioning.writer', ['format' => 'xml'])

        ->set(IncrementingStrategy::class)
            ->tag('bizkit_versioning.strategy', ['alias' => 'incrementing'])

        ->set(SemVerStrategy::class)
            ->args([
                'minor',
            ])
            ->tag('bizkit_versioning.strategy', ['alias' => 'semver'])

        ->set(GitHandler::class)
            ->args([
                param('.bizkit_versioning.file'),
                param('.bizkit_versioning.vcs_commit_message'),
                param('.bizkit_versioning.vcs_tag_message'),
                param('.bizkit_versioning.vcs_name'),
                param('.bizkit_versioning.vcs_email'),
                param('.bizkit_versioning.path_to_vcs_executable'),
            ])
            ->tag('bizkit_versioning.vcs_handler', ['alias' => 'git'])

        ->set(IncrementCommand::class)
            ->args([
                param('.bizkit_versioning.file'),
                service(ReaderInterface::class),
                service(WriterInterface::class),
                service(StrategyInterface::class),
                service(VCSHandlerInterface::class)->nullOnInvalid(),
            ])
            ->tag('console.command')
    ;
};

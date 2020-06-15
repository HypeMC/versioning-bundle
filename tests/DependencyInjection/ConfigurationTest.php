<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\DependencyInjection;

use Bizkit\VersioningBundle\DependencyInjection\Configuration;
use Bizkit\VersioningBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \Bizkit\VersioningBundle\DependencyInjection\Configuration
 */
final class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => []]);

        $this->assertSame([
            'parameter_prefix' => 'application',
            'strategy' => 'incrementing',
            'filename' => 'version',
            'filepath' => '%kernel.project_dir%/config',
            'format' => 'yaml',
            'vcs' => [
                'handler' => 'git',
                'commit_message' => null,
                'tag_message' => null,
                'name' => null,
                'email' => null,
                'path_to_executable' => null,
            ],
        ], $config);
    }

    public function testConfigWhenVCSIsTrue(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => [
            'vcs' => true,
        ]]);

        $this->assertArrayHasKey('vcs', $config);
        $this->assertSame([
            'handler' => 'git',
            'commit_message' => null,
            'tag_message' => null,
            'name' => null,
            'email' => null,
            'path_to_executable' => null,
        ], $config['vcs']);
    }

    public function testConfigWhenVCSIsFalse(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => [
            'vcs' => false,
        ]]);

        $this->assertArrayHasKey('vcs', $config);
        $this->assertSame([
            'handler' => null,
            'commit_message' => null,
            'tag_message' => null,
            'name' => null,
            'email' => null,
            'path_to_executable' => null,
        ], $config['vcs']);
    }
}

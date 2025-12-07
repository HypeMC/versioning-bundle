<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\DependencyInjection;

use Bizkit\VersioningBundle\DependencyInjection\Configuration;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\VCS\TaggingMode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \Bizkit\VersioningBundle\DependencyInjection\Configuration
 */
final class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => []]);

        self::assertSame([
            'parameter_prefix' => 'application',
            'strategy' => 'incrementing',
            'filename' => 'version',
            'filepath' => '%kernel.project_dir%/config',
            'format' => 'yaml',
            'vcs' => [
                'handler' => 'git',
                'commit_message' => 'Update application version to %s',
                'tagging_mode' => TaggingMode::Ask,
                'tag_message' => 'Update application version to %s',
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

        self::assertArrayHasKey('vcs', $config);
        self::assertSame([
            'handler' => 'git',
            'commit_message' => 'Update application version to %s',
            'tagging_mode' => TaggingMode::Ask,
            'tag_message' => 'Update application version to %s',
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

        self::assertArrayHasKey('vcs', $config);
        self::assertSame([
            'handler' => null,
            'commit_message' => 'Update application version to %s',
            'tagging_mode' => TaggingMode::Ask,
            'tag_message' => 'Update application version to %s',
            'name' => null,
            'email' => null,
            'path_to_executable' => null,
        ], $config['vcs']);
    }

    /**
     * @dataProvider provideVCSTaggingModeAsStringCases
     */
    public function testConfigWhenVCSTaggingModeIsString(string $taggingMode, TaggingMode $expectedTaggingMode): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => [
            'vcs' => [
                'tagging_mode' => $taggingMode,
            ],
        ]]);

        self::assertArrayHasKey('vcs', $config);
        self::assertSame([
            'tagging_mode' => $expectedTaggingMode,
            'handler' => 'git',
            'commit_message' => 'Update application version to %s',
            'tag_message' => 'Update application version to %s',
            'name' => null,
            'email' => null,
            'path_to_executable' => null,
        ], $config['vcs']);
    }

    public static function provideVCSTaggingModeAsStringCases(): iterable
    {
        yield ['always', TaggingMode::Always];
        yield ['ask', TaggingMode::Ask];
        yield ['never', TaggingMode::Never];
    }

    public function testConfigWhenVCSTaggingModeIsInvalidString(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid tagging mode provided: expected one of "always", "ask", "never", got "invalid_mode".');

        (new Processor())->processConfiguration(new Configuration(), ['bizkit_versioning' => [
            'vcs' => [
                'tagging_mode' => 'invalid_mode',
            ],
        ]]);
    }
}

<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\Command;

use Bizkit\VersioningBundle\Command\IncrementCommand;
use Bizkit\VersioningBundle\Reader\YamlFileReader;
use Bizkit\VersioningBundle\Strategy\IncrementingStrategy;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\VCS\GitHandler;
use Bizkit\VersioningBundle\Writer\YamlFileWriter;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Bizkit\VersioningBundle\Command\IncrementCommand
 */
final class IncrementCommandTest extends TestCase
{
    /**
     * @var string|null
     */
    private $validFile;

    /**
     * @var string|null
     */
    private $invalidFile;

    protected function setUp(): void
    {
        $this->validFile = sys_get_temp_dir().'/version.yaml';
        $this->invalidFile = sys_get_temp_dir().'/invalid-version-format.yaml';

        copy(__DIR__.'/Fixtures/version.yaml', $this->validFile);
        copy(__DIR__.'/Fixtures/invalid-version-format.yaml', $this->invalidFile);
    }

    protected function tearDown(): void
    {
        unlink($this->validFile);
        unlink($this->invalidFile);

        $this->validFile = null;
        $this->invalidFile = null;
    }

    public function testExceptionIsThrownForInvalidTaggingMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid VCS tagging mode "invalid". Expected one of: "always", "never", "ask".');

        $this->createCommandTester($this->validFile, null, 'invalid');
    }

    public function testVersionIsIncremented(): void
    {
        $commandTester = $this->createCommandTester($this->validFile);
        $commandTester->setInputs(['yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertFileExists($this->validFile);

        $yaml = Yaml::parseFile($this->validFile);
        self::assertArrayHasKey('parameters', $yaml);
        self::assertArrayHasKey('app.version', $yaml['parameters']);

        self::assertSame('2', $yaml['parameters']['app.version']);
    }

    public function testVersionIsInitialized(): void
    {
        unlink($this->validFile);

        $commandTester = $this->createCommandTester($this->validFile);
        $commandTester->setInputs(['yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your application doesn\'t have a version set, do you wish to initialize it?', $display);
        self::assertStringContainsString('Your application version has been initialized to "1".', $display);
        self::assertFileExists($this->validFile);

        $yaml = Yaml::parseFile($this->validFile);
        self::assertArrayHasKey('parameters', $yaml);
        self::assertArrayHasKey('app.version', $yaml['parameters']);

        self::assertSame('1', $yaml['parameters']['app.version']);
    }

    public function testVersionIsNotIncrementedWhenConfirmationIsFalse(): void
    {
        $commandTester = $this->createCommandTester($this->validFile);
        $commandTester->setInputs(['no']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertFileExists($this->validFile);

        $yaml = Yaml::parseFile($this->validFile);
        self::assertArrayHasKey('parameters', $yaml);
        self::assertArrayHasKey('app.version', $yaml['parameters']);

        self::assertSame('1', $yaml['parameters']['app.version']);
    }

    public function testCommandFailsOnInvalidVersionFormat(): void
    {
        $commandTester = $this->createCommandTester($this->invalidFile);
        $commandTester->setInputs(['yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Your current application version is "1.2", do you wish to increment it?', $display);
        self::assertStringContainsString('Failed incrementing to a new version:', $display);
        self::assertFileExists($this->invalidFile);

        $yaml = Yaml::parseFile($this->invalidFile);
        self::assertArrayHasKey('parameters', $yaml);
        self::assertArrayHasKey('app.version', $yaml['parameters']);

        self::assertSame('1.2', $yaml['parameters']['app.version']);
    }

    public function testCommandFailsWhenNewVersionCannotBeStored(): void
    {
        chmod($this->validFile, 0400);

        $commandTester = $this->createCommandTester($this->validFile);
        $commandTester->setInputs(['yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Failed storing new version "2":', $display);
        self::assertFileExists($this->validFile);

        $yaml = Yaml::parseFile($this->validFile);
        self::assertArrayHasKey('parameters', $yaml);
        self::assertArrayHasKey('app.version', $yaml['parameters']);

        self::assertSame('1', $yaml['parameters']['app.version']);
    }

    public function testFileIsCommittedAndTagIsCreatedIfVCSHandlerIsNotNullAndConfirmationIsTrue(): void
    {
        $commandTester = $this->createCommandTester($this->validFile, __DIR__.'/Fixtures/fake-git/success.php');
        $commandTester->setInputs(['yes', 'yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertStringContainsString('Your application version file has successfully been committed to your VCS.', $display);
        self::assertStringContainsString('Do you wish to create a tag with the version "2"?', $display);
        self::assertStringContainsString('Your application has successfully been tagged with the version "2".', $display);
    }

    public function testFileIsCommittedAndTagIsNotCreatedIfVCSHandlerIsNotNullAndConfirmationIsFalse(): void
    {
        $commandTester = $this->createCommandTester($this->validFile, __DIR__.'/Fixtures/fake-git/success.php');
        $commandTester->setInputs(['yes', 'no']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertStringContainsString('Your application version file has successfully been committed to your VCS.', $display);
        self::assertStringContainsString('Do you wish to create a tag with the version "2"?', $display);
        self::assertStringNotContainsString('Your application has successfully been tagged with the version "2".', $display);
    }

    public function testFileIsCommittedAndTagIsAutocreatedIfVCSHandlerIsNotNullAndTaggingModeIsAlways(): void
    {
        $commandTester = $this->createCommandTester($this->validFile, __DIR__.'/Fixtures/fake-git/success.php', 'always');
        $commandTester->setInputs(['yes', 'yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertStringContainsString('Your application version file has successfully been committed to your VCS.', $display);
        self::assertStringNotContainsString('Do you wish to create a tag with the version "2"?', $display);
        self::assertStringContainsString('Your application has successfully been tagged with the version "2".', $display);
    }

    public function testFileIsCommittedAndTagIsNotPromptedIfVCSHandlerIsNotNullAndTaggingModeIsNever(): void
    {
        $commandTester = $this->createCommandTester($this->validFile, __DIR__.'/Fixtures/fake-git/success.php', 'never');
        $commandTester->setInputs(['yes', 'yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertStringContainsString('Your application version file has successfully been committed to your VCS.', $display);
        self::assertStringNotContainsString('Do you wish to create a tag with the version "2"?', $display);
        self::assertStringNotContainsString('Your application has successfully been tagged with the version "2".', $display);
    }

    public function testVCSHandlerErrorIsSentToOutput(): void
    {
        $commandTester = $this->createCommandTester($this->validFile, __DIR__.'/Fixtures/fake-git/fail.php');
        $commandTester->setInputs(['yes', 'yes']);

        $statusCode = $commandTester->execute([]);
        $display = $commandTester->getDisplay();

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Your current application version is "1", do you wish to increment it?', $display);
        self::assertStringContainsString('Your application version has been incremented to "2".', $display);
        self::assertStringContainsString('Your application version file has successfully been committed to your VCS.', $display);
        self::assertStringContainsString('Do you wish to create a tag with the version "2"?', $display);
        self::assertStringContainsString('Cannot create the tag "v2" as it already exists.', $display);
    }

    private function createCommandTester(string $file, ?string $pathToVCSExecutable = null, string $taggingMode = 'ask'): CommandTester
    {
        $vcs = null !== $pathToVCSExecutable ? new GitHandler($file, null, null, null, null, $pathToVCSExecutable) : null;

        return new CommandTester(
            new IncrementCommand(
                $file,
                new YamlFileReader($file, 'app'),
                new YamlFileWriter($file, 'app'),
                new IncrementingStrategy(),
                $vcs,
                $taggingMode
            )
        );
    }
}

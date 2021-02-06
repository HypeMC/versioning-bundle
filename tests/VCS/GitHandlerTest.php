<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Tests\VCS;

use Bizkit\VersioningBundle\Exception\VCSException;
use Bizkit\VersioningBundle\Tests\TestCase;
use Bizkit\VersioningBundle\VCS\GitHandler;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * @covers \Bizkit\VersioningBundle\VCS\GitHandler
 */
final class GitHandlerTest extends TestCase
{
    private const VERSION_FILE = __DIR__.'/Fixtures/version.yaml';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|StyleInterface|null
     */
    private $io;

    protected function setUp(): void
    {
        $this->io = $this->createMock(StyleInterface::class);
    }

    protected function tearDown(): void
    {
        $this->io = null;
    }

    public function testDefaultMessagesAreUsed(): void
    {
        $version = new Version('1.2.3');

        $this->io->expects($this->exactly(9))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['diff exists'],
            [sprintf('Committing the file "%s".', self::VERSION_FILE)],
            ['commit successful'],
            [sprintf('Checking if the tag "v%s" already exists.', $version)],
            [sprintf('Creating a new tag "v%s".', $version)],
            ['tag created']
        );

        $this->io->expects($this->once())->method('error')->withConsecutive(
            ['tag does not exist']
        );

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/default-settings.php', null, null);

        $handler->commit($this->io, $version);
        $handler->tag($this->io, $version);
    }

    public function testNameAndEmailAreNotUsedIfNull(): void
    {
        $this->io->expects($this->exactly(6))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['diff exists'],
            [sprintf('Committing the file "%s".', self::VERSION_FILE)],
            ['commit successful']
        );

        $this->io->expects($this->never())->method('error');

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/default-settings.php', null, null);

        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testNameAndEmailAreUsedIfExist(): void
    {
        $this->io->expects($this->exactly(6))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['diff exists'],
            [sprintf('Committing the file "%s".', self::VERSION_FILE)],
            ['commit successful']
        );

        $this->io->expects($this->never())->method('error');

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/custom-username-and-email.php', null, null, 'Some Name', 'test@email.com');

        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testCommitIsCreatedSuccessfully(): void
    {
        $this->io->expects($this->exactly(6))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['diff exists'],
            [sprintf('Committing the file "%s".', self::VERSION_FILE)],
            ['commit successful']
        );

        $this->io->expects($this->never())->method('error');

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/commit-created-successfully.php');
        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testExceptionIsThrownIfStageFails(): void
    {
        $this->io->expects($this->once())->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)]
        );

        $this->io->expects($this->once())->method('error')->withConsecutive(
            ['stage failed']
        );

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/stage-failed.php');

        $this->expectException(VCSException::class);
        $this->expectExceptionMessage(sprintf('Failed to stage the file "%s".', self::VERSION_FILE));

        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testExceptionIsThrownIfThereIsNothingToCommit(): void
    {
        $this->io->expects($this->exactly(4))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['nothing to commit']
        );

        $this->io->expects($this->never())->method('error');

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/nothing-to-commit.php');

        $this->expectException(VCSException::class);
        $this->expectExceptionMessage(sprintf('There are no changes to the file "%s".', self::VERSION_FILE));

        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testExceptionIsThrownIfCommitCreationFails(): void
    {
        $this->io->expects($this->exactly(5))->method('text')->withConsecutive(
            [sprintf('Staging the file "%s".', self::VERSION_FILE)],
            ['stage successful'],
            [sprintf('Checking if the file "%s" has any changes to commit.', self::VERSION_FILE)],
            ['diff exists'],
            [sprintf('Committing the file "%s".', self::VERSION_FILE)]
        );

        $this->io->expects($this->once())->method('error')->withConsecutive(
            ['commit creation failed']
        );

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/commit-creation-failed.php');

        $this->expectException(VCSException::class);
        $this->expectExceptionMessage(sprintf('Failed to commit the file "%s".', self::VERSION_FILE));

        $handler->commit($this->io, new Version('1.2.3'));
    }

    public function testTagIsCreatedSuccessfully(): void
    {
        $version = new Version('1.2.3');

        $this->io->expects($this->exactly(3))->method('text')->withConsecutive(
            [sprintf('Checking if the tag "v%s" already exists.', $version)],
            [sprintf('Creating a new tag "v%s".', $version)],
            ['tag created']
        );

        $this->io->expects($this->once())->method('error')->withConsecutive(
            ['tag does not exist']
        );

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/tag-created-successfully.php');
        $handler->tag($this->io, $version);
    }

    public function testExceptionIsThrownIfTagExists(): void
    {
        $version = new Version('1.2.3');

        $this->io->expects($this->exactly(2))->method('text')->withConsecutive(
            [sprintf('Checking if the tag "v%s" already exists.', $version)],
            ['tag exists']
        );

        $this->io->expects($this->never())->method('error');

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/tag-exists.php');

        $this->expectException(VCSException::class);
        $this->expectExceptionMessage(sprintf('Cannot create the tag "v%s" as it already exists.', $version));

        $handler->tag($this->io, $version);
    }

    public function testExceptionIsThrownIfTagCreationFails(): void
    {
        $version = new Version('1.2.3');

        $this->io->expects($this->exactly(2))->method('text')->withConsecutive(
            [sprintf('Checking if the tag "v%s" already exists.', $version)],
            [sprintf('Creating a new tag "v%s".', $version)]
        );

        $this->io->expects($this->exactly(2))->method('error')->withConsecutive(
            ['tag does not exist'],
            ['tag creation failed']
        );

        $handler = $this->createHandler(__DIR__.'/Fixtures/fake-git/tag-creation-failed.php');

        $this->expectException(VCSException::class);
        $this->expectExceptionMessage(sprintf('Failed to create the tag "v%s".', $version));

        $handler->tag($this->io, $version);
    }

    private function createHandler(
        string $pathToExecutable,
        ?string $commitMessage = 'Commit msg %s',
        ?string $tagMessage = 'Tag msg %s',
        ?string $vcsName = null,
        ?string $vcsEmail = null
    ): GitHandler {
        return new GitHandler(
            self::VERSION_FILE,
            $commitMessage,
            $tagMessage,
            $vcsName,
            $vcsEmail,
            $pathToExecutable
        );
    }
}

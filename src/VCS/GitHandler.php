<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\VCS;

use Bizkit\VersioningBundle\Exception\VCSException;
use Bizkit\VersioningBundle\Version;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class GitHandler implements VCSHandlerInterface
{
    private const DEFAULT_MESSAGE = 'Update application version to %s';

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $commitMessage;

    /**
     * @var string
     */
    private $tagMessage;

    /**
     * @var string[]
     */
    private $baseCommand = [];

    public function __construct(
        string $file,
        ?string $commitMessage = null,
        ?string $tagMessage = null,
        ?string $vcsName = null,
        ?string $vcsEmail = null,
        ?string $pathToExecutable = null
    ) {
        $pathToExecutable = $pathToExecutable ?? (new ExecutableFinder())->find('git');

        if (null === $pathToExecutable) {
            throw new VCSException('Unable to find the Git executable.');
        }

        $this->file = $file;

        $this->commitMessage = $commitMessage ?? self::DEFAULT_MESSAGE;
        $this->tagMessage = $tagMessage ?? self::DEFAULT_MESSAGE;

        $this->baseCommand[] = $pathToExecutable;

        if (null !== $vcsName) {
            $this->baseCommand[] = '-c';
            $this->baseCommand[] = sprintf('user.name="%s"', $vcsName);
        }
        if (null !== $vcsEmail) {
            $this->baseCommand[] = '-c';
            $this->baseCommand[] = sprintf('user.email="%s"', $vcsEmail);
        }
    }

    public function commit(StyleInterface $io, Version $version): void
    {
        try {
            $io->text(sprintf('Staging the file "%s".', $this->file));
            $this->executeCommand($io, ['add', $this->file]);
        } catch (ProcessFailedException $e) {
            throw new VCSException(sprintf('Failed to stage the file "%s".', $this->file), $e);
        }

        try {
            $io->text(sprintf('Checking if the file "%s" has any changes to commit.', $this->file));
            $this->executeCommand($io, ['diff', '--cached', '--exit-code', '--quiet', $this->file]);

            throw new VCSException(sprintf('There are no changes to the file "%s".', $this->file));
        } catch (ProcessFailedException $e) {
            try {
                $io->text(sprintf('Committing the file "%s".', $this->file));
                $this->executeCommand($io, ['commit', '-m', sprintf($this->commitMessage, $version), $this->file]);
            } catch (ProcessFailedException $e) {
                throw new VCSException(sprintf('Failed to commit the file "%s".', $this->file), $e);
            }
        }
    }

    public function tag(StyleInterface $io, Version $version): void
    {
        $tag = 'v'.$version;

        try {
            $io->text(sprintf('Checking if the tag "%s" already exists.', $tag));
            $this->executeCommand($io, ['rev-parse', '--quiet', '--verify', sprintf('refs/tags/%s', $tag)]);

            throw new VCSException(sprintf('Cannot create the tag "%s" as it already exists.', $tag));
        } catch (ProcessFailedException $e) {
            try {
                $io->text(sprintf('Creating a new tag "%s".', $tag));
                $this->executeCommand($io, ['tag', '-a', $tag, '-m', sprintf($this->tagMessage, $version)]);
            } catch (ProcessFailedException $e) {
                throw new VCSException(sprintf('Failed to create the tag "%s".', $tag), $e);
            }
        }
    }

    private function executeCommand(StyleInterface $io, array $command): void
    {
        $process = new Process(array_merge($this->baseCommand, $command));

        $process->run(static function (string $type, string $buffer) use ($io): void {
            if (Process::ERR === $type) {
                $io->error($buffer);
            } else {
                $io->text($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}

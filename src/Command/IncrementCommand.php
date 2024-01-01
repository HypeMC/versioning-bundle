<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Command;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Exception\StorageException;
use Bizkit\VersioningBundle\Exception\VCSException;
use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;
use Bizkit\VersioningBundle\Version;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    'bizkit:versioning:increment',
    'Increments the version using the configured versioning strategy.',
)]
final class IncrementCommand extends Command
{
    public function __construct(
        private readonly string $file,
        private readonly ReaderInterface $reader,
        private readonly WriterInterface $writer,
        private readonly StrategyInterface $strategy,
        private readonly ?VCSHandlerInterface $vcsHandler = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $version = is_file($this->file) ? $this->reader->read() : null;

        $confirmationQuestion = null === $version
            ? 'Your application doesn\'t have a version set, do you wish to initialize it?'
            : sprintf('Your current application version is "%s", do you wish to increment it?', $version);

        if (!$io->confirm($confirmationQuestion, true)) {
            return self::SUCCESS;
        }

        try {
            $newVersion = ($this->strategy)($io, $version);
        } catch (InvalidVersionFormatException $e) {
            $io->error(sprintf('Failed incrementing to a new version: %s', $e->getMessage()));

            return self::FAILURE;
        }

        try {
            $this->writer->write($newVersion);
        } catch (StorageException $e) {
            $io->error(sprintf('Failed storing new version "%s": %s', $newVersion, $e->getMessage()));

            return self::FAILURE;
        }

        $io->success(sprintf(
            'Your application version has been %s to "%s".',
            null === $version ? 'initialized' : 'incremented',
            $newVersion,
        ));

        try {
            $this->commitAndTag($io, $newVersion);
        } catch (VCSException $e) {
            $io->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function commitAndTag(StyleInterface $io, Version $version): void
    {
        if (null === $this->vcsHandler) {
            return;
        }

        $this->vcsHandler->commit($io, $version);
        $io->success('Your application version file has successfully been committed to your VCS.');

        if ($io->confirm(sprintf('Do you wish to create a tag with the version "%s"?', $version), true)) {
            $this->vcsHandler->tag($io, $version);
            $io->success(sprintf('Your application has successfully been tagged with the version "%s".', $version));
        }
    }
}

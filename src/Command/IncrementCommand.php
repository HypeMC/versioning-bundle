<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\Command;

use Bizkit\VersioningBundle\Exception\InvalidVersionFormatException;
use Bizkit\VersioningBundle\Exception\StorageException;
use Bizkit\VersioningBundle\Reader\ReaderInterface;
use Bizkit\VersioningBundle\Strategy\StrategyInterface;
use Bizkit\VersioningBundle\Writer\WriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class IncrementCommand extends Command
{
    protected static $defaultName = 'bizkit:versioning:increment';

    /**
     * @var string
     */
    private $file;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var StrategyInterface
     */
    private $strategy;

    public function __construct(string $file, ReaderInterface $reader, WriterInterface $writer, StrategyInterface $strategy)
    {
        $this->file = $file;
        $this->reader = $reader;
        $this->writer = $writer;
        $this->strategy = $strategy;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Increments the version using the configured versioning strategy.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $version = is_file($this->file) ? $this->reader->read() : null;

        $confirmationQuestion = null === $version
            ? 'Your application doesn\'t have a version set, do you wish to initialize it?'
            : sprintf('Your current application version is "%s", do you wish to increment it?', $version);

        if (!$io->confirm($confirmationQuestion, true)) {
            return 0;
        }

        try {
            $newVersion = ($this->strategy)($io, $version);
        } catch (InvalidVersionFormatException $e) {
            $io->error(sprintf('Failed incrementing to a new version: %s', $e->getMessage()));

            return 1;
        }

        try {
            $this->writer->write($newVersion);
        } catch (StorageException $e) {
            $io->error(sprintf('Failed storing new version "%s": %s', $newVersion, $e->getMessage()));

            return 1;
        }

        $io->success(sprintf(
            'Your application version has been %s to "%s".',
            null === $version ? 'initialized' : 'incremented',
            $newVersion
        ));

        return 0;
    }
}

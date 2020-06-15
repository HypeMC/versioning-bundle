#!/usr/bin/env php
<?php

$file = dirname(__DIR__).'/version.yaml';

switch ($command = implode(' ', array_slice($argv, 1))) {
    case sprintf('add %s', $file):
        fwrite(STDERR, 'stage failed');
        exit(1);
    default:
        throw new InvalidArgumentException(sprintf('Invalid command "%s".', $command));
}

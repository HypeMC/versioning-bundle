#!/usr/bin/env php
<?php

$file = dirname(__DIR__).'/version.yaml';

switch ($command = implode(' ', array_slice($argv, 1))) {
    case 'rev-parse --quiet --verify refs/tags/v1.2.3':
        echo 'tag exists';
        exit(0);
    default:
        throw new InvalidArgumentException(sprintf('Invalid command "%s".', $command));
}

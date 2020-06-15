#!/usr/bin/env php
<?php

$file = dirname(__DIR__).'/version.yaml';

switch ($command = implode(' ', array_slice($argv, 1))) {
    case sprintf('add %s', $file):
        echo 'stage successful';
        exit(0);
    case sprintf('diff --cached --exit-code --quiet %s', $file):
        echo 'nothing to commit';
        exit(0);
    default:
        throw new InvalidArgumentException(sprintf('Invalid command "%s".', $command));
}

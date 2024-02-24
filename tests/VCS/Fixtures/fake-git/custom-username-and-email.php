#!/usr/bin/env php
<?php

declare(strict_types=1);

$file = dirname(__DIR__).'/version.yaml';

switch ($command = implode(' ', array_slice($argv, 1))) {
    case sprintf('-c user.name="Some Name" -c user.email="test@email.com" add %s', $file):
        echo 'stage successful';
        exit(0);
    case sprintf('-c user.name="Some Name" -c user.email="test@email.com" diff --cached --exit-code --quiet %s', $file):
        echo 'diff exists';
        exit(1);
    case sprintf('-c user.name="Some Name" -c user.email="test@email.com" commit -m Update application version to 1.2.3 %s', $file):
        echo 'commit successful';
        exit(0);
    default:
        throw new InvalidArgumentException(sprintf('Invalid command "%s".', $command));
}

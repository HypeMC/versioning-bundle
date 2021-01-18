#!/usr/bin/env php
<?php

$file = sys_get_temp_dir().'/version.yaml';

switch ($command = implode(' ', array_slice($argv, 1))) {
    case sprintf('add %s', $file):
        echo 'stage successful';
        exit(0);
    case sprintf('diff --cached --exit-code --quiet %s', $file):
        echo 'diff exists';
        exit(1);
    case sprintf('commit -m Update application version to 2 %s', $file):
        echo 'commit successful';
        exit(0);
    case 'rev-parse --quiet --verify refs/tags/v2':
        fwrite(\STDERR, 'tag does not exists');
        exit(1);
    case 'tag -a v2 -m Update application version to 2':
        echo 'tag created';
        exit(0);
    default:
        throw new InvalidArgumentException(sprintf('Invalid command "%s".', $command));
}

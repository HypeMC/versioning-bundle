<?php

declare(strict_types=1);

$getEnvVar = static function ($name, $default = false) {
    if (false !== $value = getenv($name)) {
        return $value;
    }

    static $phpunitConfig = null;
    if (null === $phpunitConfig) {
        $phpunitConfigFilename = null;
        if (file_exists('phpunit.xml')) {
            $phpunitConfigFilename = 'phpunit.xml';
        } elseif (file_exists('phpunit.xml.dist')) {
            $phpunitConfigFilename = 'phpunit.xml.dist';
        }

        $phpunitConfig = false;
        if ($phpunitConfigFilename) {
            $phpunitConfig = new DomDocument();
            $phpunitConfig->load($phpunitConfigFilename);
        }
    }

    if (false !== $phpunitConfig) {
        $var = new DOMXpath($phpunitConfig);
        foreach ($var->query('//php/server[@name="'.$name.'"]') as $var) {
            return $var->getAttribute('value');
        }
        foreach ($var->query('//php/env[@name="'.$name.'"]') as $var) {
            return $var->getAttribute('value');
        }
    }

    return $default;
};

$SYMFONY_PHPUNIT_DIR = $getEnvVar('SYMFONY_PHPUNIT_DIR', __DIR__.'/vendor/bin/.phpunit');
$SYMFONY_PHPUNIT_VERSION = $getEnvVar('SYMFONY_PHPUNIT_VERSION');

require "$SYMFONY_PHPUNIT_DIR/phpunit-$SYMFONY_PHPUNIT_VERSION-0/vendor/autoload.php";

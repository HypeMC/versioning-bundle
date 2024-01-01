# BizkitVersioningBundle

[![Build Status](https://github.com/HypeMC/versioning-bundle/workflows/Tests/badge.svg)](https://github.com/HypeMC/versioning-bundle/actions)
[![Latest Stable Version](https://poser.pugx.org/bizkit/versioning-bundle/v/stable)](https://packagist.org/packages/bizkit/versioning-bundle)
[![License](https://poser.pugx.org/bizkit/versioning-bundle/license)](https://packagist.org/packages/bizkit/versioning-bundle)
[![Code Coverage](https://codecov.io/gh/HypeMC/versioning-bundle/branch/2.x/graph/badge.svg)](https://codecov.io/gh/HypeMC/versioning-bundle)

Symfony bundle which provides a way to version your application using various versioning strategies.

![Showcase](showcase.gif)

## Features

* Stores the application's version & release date into a compliant YAML or XML Symfony container configuration file
* Automatically imports the file with the parameters into Symfony's container
* Supports multiple versioning strategies & creating custom ones
* Includes a console command for incrementing the version using the configured versioning strategy
* The command automatically commits the version file & optionally creates a tag if a VCS handler is configured
* Supports the Git VCS & creating custom VCS handlers

## Requirements

* [PHP 8.1](http://php.net/releases/8_1_0.php) or greater
* [Symfony 5.4](https://symfony.com/roadmap/5.4) or [Symfony 6.4](https://symfony.com/roadmap/6.4) or greater

## Installation

1. Require the bundle with [Composer](https://getcomposer.org/):

    ```sh
    composer require bizkit/versioning-bundle
    ```

1. Create the bundle configuration file under `config/packages/bizkit_versioning.yaml`. Here is a reference configuration file:

    ```yaml
    bizkit_versioning:

        # The prefix added to the version parameters.
        parameter_prefix:     application # Example: my_app

        # The versioning strategy used.
        strategy:             incrementing

        # The name of the file containing the version information.
        filename:             version

        # The path to the file containing the version information.
        filepath:             '%kernel.project_dir%/config'

        # The format used for the version file.
        format:               yaml # One of "yaml"; "xml"

        # Configuration for the VCS integration,
        # set to false to disable the integration.
        vcs:

            # The handler used for the VCS integration,
            # set to null to disable the integration.
            handler:              git

            # The message to use for the VCS commit.
            commit_message:       null

            # The message to use for the VCS tag.
            tag_message:          null

            # The name used for the VCS commit information,
            # set to null to use the default VCS configuration.
            name:                 null

            # The email used for the VCS commit information,
            # set to null to use the default VCS configuration.
            email:                null

            # The path to the VCS executable,
            # set to null for autodiscovery.
            path_to_executable:   null
    ```

1. Enable the bundle in `config/bundles.php` by adding it to the array:

    ```php
    Bizkit\VersioningBundle\BizkitVersioningBundle::class => ['all' => true],
    ```

## Usage

The bundle creates a compliant [Symfony Dependency Injection Container](https://symfony.com/doc/4.4/components/dependency_injection.html)
configuration file with the following three parameters:

- `application.version` - the application's version (the format depends on the configured versioning strategy)
- `application.version_hash` - an MD5 digest of the version
- `application.release_date` - an [RFC 3339](https://tools.ietf.org/html/rfc3339) formatted date on which the version was last incremented

> **NOTE:** The parameter names mighty vary depending on the `parameter_prefix` configuration option.

It automatically adds the parameters into Symfony's container by registering the file as an [import](https://symfony.com/doc/4.4/service_container/import.html).

To find out more about parameters check Symfony's [official documentation](https://symfony.com/doc/4.4/configuration.html#configuration-parameters).
The following example shows how to use it with [Sentry's](https://sentry.io/) Monolog handler:

```yaml
monolog:
    sentry:
        type: sentry
        dsn: '%sentry_dsn%'
        release: '%application.version%'
```

### Incrementing the version

To increment the version using the configured strategy run the following command:

```sh
bin/console bizkit:versioning:increment
```

If you have a VCS handler configured, the command will automatically commit the version file
& optionally create a tag with the new version.

## Versioning strategies

The bundle comes with the following version strategies:

1. `incrementing` - defines the version as an incrementing number
1. `semver` - uses the [Semantic Versioning](https://semver.org/) system

### Custom strategies

To implement a custom strategy all you need to do is create a service which implements the `StrategyInterface` interface.

```php
namespace App;

use Bizkit\VersioningBundle\Strategy\StrategyInterface;

class MyStrategy implements StrategyInterface
{
    public function __invoke(StyleInterface $io, ?Version $version = null): Version
    {
        if (null === $version) {
            // return initial version
        }

        // return incremented version
    }
}
```

Use the FQCN of the service in the configuration:

```yaml
bizkit_versioning:
    strategy: App\MyStrategy
```

If you are not using Symfony's [autoconfigure](https://symfony.com/doc/4.4/service_container.html#the-autoconfigure-option)
feature or wish to use an alias in the configuration, tag the service with the `bizkit_versioning.strategy` tag.

```yaml
App\MyStrategy:
    tags:
        - { name: 'bizkit_versioning.strategy', alias: 'my_strategy' }

bizkit_versioning:
    strategy: my_strategy
```

## VCS handlers

The bundle comes with a handler for the [Git](https://git-scm.com/) VCS. If you wish to disable the VCS feature,
set the `vcs` configuration option to `false`:

```yaml
bizkit_versioning:
    vcs: false
```

### Custom VCS handlers

To implement a custom VCS handler all you need to do is create a service which implements the `VCSHandlerInterface` interface.

```php
namespace App;

use Bizkit\VersioningBundle\VCS\VCSHandlerInterface;

class MyVCSHandler implements VCSHandlerInterface
{
    public function commit(StyleInterface $io, Version $version): void
    {
        // commit the file
    }

    public function tag(StyleInterface $io, Version $version): void
    {
        // create a tag
    }
}
```

Use the FQCN of the service in the configuration:

```yaml
bizkit_versioning:
    vcs:
        handler: App\MyVCSHandler
```

If you are not using Symfony's [autoconfigure](https://symfony.com/doc/4.4/service_container.html#the-autoconfigure-option)
feature or wish to use an alias in the configuration, tag the service with the `bizkit_versioning.vcs_handler` tag.

```yaml
App\MyVCSHandler:
    tags:
        - { name: 'bizkit_versioning.vcs_handler', alias: 'my_vcs' }

bizkit_versioning:
    vcs:
        handler: my_vcs
```

## Versioning

This project adheres to [Semantic Versioning 2.0.0](http://semver.org/).

## Reporting issues

Use the [issue tracker](https://github.com/HypeMC/versioning-bundle/issues) to report any issues you might have.

## License

See the [LICENSE](LICENSE) file for license rights and limitations (MIT).

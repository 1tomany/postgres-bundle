# Doctrine Postgres Bundle for Symfony

This package enhances Postgres for Doctrine.

## Installation

Install the bundle using Composer:

```
composer require 1tomany/postgres-bundle
```

## Configuration

This bundle will automatically use the default Doctrine DBAL connection object configured with a standard Symfony installation. If you wish to change this, create a file named `postgres.yaml` in `config/packages/` with the following contents and replace the `advisory_lock_manager.connection` property with the service ID of the DBAL connection to use:

```yaml
postgres:
    advisory_lock_manager:
        connection: 'doctrine.dbal.non_default_connection'

    middleware:
        time_zone: 'America/Chicago'
```

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License

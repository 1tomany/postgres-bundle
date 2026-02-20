# Doctrine Postgres Bundle for Symfony

This package enhances Postgres for Doctrine.

## Installation

Install the bundle using Composer:

```
composer require 1tomany/postgres-bundle
```

## Configuration

This bundle is automatically configured to work with the default Doctrine DBAL connection. To change the default configuration, create a file named `postgres.yaml` in `config/packages/` with the following contents and adjust accordingly:

```yaml
postgres:
    advisory_lock_manager:
        connection: "doctrine.dbal.default_connection"

    middleware:
        time_zone: "UTC"
```

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License

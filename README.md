# Doctrine Postgres Bundle for Symfony

This package enhances Postgres for Doctrine.

## Installation

Install the bundle using Composer:

```
composer require 1tomany/postgres-bundle
```

## Configuration

By default, this bundle will automatically set the timezone of the Postgres server to UTC. If you wish to change this, create a file named `postgres.yaml` in `config/packages/` and add the following lines:

```yaml
postgres:
    middleware:
        time_zone: 'America/Chicago'
```

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License

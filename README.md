<a href="https://bas.dev">
    <img src="https://bmcdn.nl/assets/branding/logo.svg" alt="Bas Milius" height="48" />
</a>

---

# Raxos Cache

A Redis client with typed command wrappers, cached computations and invalidation by tag.

[Documentation](https://raxos.dev/cache/) | [Packagist](https://packagist.org/packages/raxos/cache) | [Raxos](https://github.com/basmilius/raxos)

- String, hash, list, set, sorted-set, bitmap, scripting and pub/sub commands.
- Tagged entries that can be invalidated together without scanning the keyspace.
- Tag invalidation in atomic batches of 500 keys.

## Installation

Requires PHP 8.5 or later. Enable the `redis` PHP extension. Composer checks the remaining package and extension dependencies declared in [composer.json](composer.json).

```sh
composer require "raxos/cache:^3.3"
```

## Usage

```php
<?php
declare(strict_types=1);

use Raxos\Cache\Redis\RedisCache;

require __DIR__ . '/vendor/autoload.php';

$cache = new RedisCache(prefix: 'catalog:', host: '127.0.0.1', port: 6379);
$products = $cache->tags(['products']);

$name = $products->remember('name:42', 300, static fn(): string => 'Keyboard');

$products->flush();
```

This example needs a running Redis server. TTLs are expressed in seconds. The prefix namespaces tagged entries; plain Redis commands use the keys you supply. Redis stores strings, so encode structured values or add serialization in a cache subclass.

## Documentation

- [Basic usage](https://raxos.dev/cache/basic-usage)
- [Tagged caching](https://raxos.dev/cache/tagged-cache)
- [Command groups](https://raxos.dev/cache/command-groups)

## Testing

Run this library's Pest suite from the Raxos workspace:

```sh
git clone --recurse-submodules https://github.com/basmilius/raxos.git
cd raxos
composer install
vendor/bin/pest --testsuite=cache
```

See [Testing Raxos](https://github.com/basmilius/raxos/blob/main/TESTING.md) for PHP extensions, integration services and coverage commands. The library's [Tests workflow](.github/workflows/tests.yml) also runs in GitHub Actions.

## License

[MIT](LICENSE). Copyright (c) 2017 - present Bas Milius.

See [lookups and cached computation](https://raxos.dev/cache/lookups-and-locks) for the optional APIs and their lifetime or transport guarantees.

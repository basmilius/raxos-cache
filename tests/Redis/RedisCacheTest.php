<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Error\RedisCommandFailedException;
use Raxos\Cache\Redis\RedisCache;
use RaxosTests\Cache\UnitRedisCache;
use function RaxosTests\Cache\withRedisUnit;

covers(RedisCache::class);

it('supports deferred connection and reconnecting explicitly', function (): void {
    if (getenv('RAXOS_REDIS_HOST') === false) {
        test()->markTestSkipped('Set RAXOS_REDIS_HOST to run Redis tests.');
    }
    $cache = new RedisCache('deferred', getenv('RAXOS_REDIS_HOST'), (int)(getenv('RAXOS_REDIS_PORT') ?: 0), connect: false);
    expect($cache->getPrefix())->toBe('deferred')->and($cache->isConnected())->toBeFalse();
    expect($cache->connect())->toBeTrue()->and($cache->isConnected())->toBeTrue();
});

it('does not cache failed factories and retries on the next call', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        expect(fn() => $cache->remember($prefix, 60, static fn() => throw new LogicException('factory')))->toThrow(LogicException::class);
        expect($cache->exists($prefix))->toBeFalse()->and($cache->remember($prefix, 60, static fn() => 'recovered'))->toBe('recovered');
    });
});

it('selects independent databases without moving cached keys', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $cache->set($prefix, 'zero');

        try {
            $cache->selectDatabase(1);
            expect($cache->get($prefix))->toBeFalse();
        } finally {
            $cache->selectDatabase(0);
        }
        expect($cache->get($prefix))->toBe('zero');
    });
});

it('distinguishes missing, null, false and empty serialized values atomically', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $connection = new Redis();
        $connection->connect($cache->host, $cache->port);
        $connection->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $serialized = new UnitRedisCache($connection);

        foreach ([null, false, '', 0, [], ['id' => 42]] as $index => $value) {
            $key = $prefix . ':lookup:' . $index;
            $serialized->setex($key, $value, 60);
            $entry = $serialized->lookup($key);
            expect($entry->found)->toBeTrue()->and($entry->value)->toBe($value)
                ->and($serialized->remember($key, 60, static fn() => throw new LogicException('Must be a hit')))->toBe($value);
        }
        expect($serialized->lookup($prefix . ':missing')->found)->toBeFalse();
    });
});

it('releases locks after failed factories and caches the next result', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        expect(fn() => $cache->rememberLocked($prefix, 60, static fn() => throw new LogicException('factory')))->toThrow(LogicException::class);
        expect($cache->keys($prefix . ':lock:*'))->toBe([])
            ->and($cache->rememberLocked($prefix, 60, static fn() => 'ready'))->toBe('ready')
            ->and($cache->rememberLocked($prefix, 60, static fn() => throw new LogicException('hit')))->toBe('ready');
    });
});

it('does not publish or release another owner after losing its lease', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $other = new RedisCache($prefix, $cache->host, $cache->port);
        $lock = $prefix . ':lock:' . hash('sha256', $prefix);
        $calculate = static function () use ($other, $lock): string {
            $other->eval("return redis.call('SET', KEYS[1], 'next-owner', 'EX', 30)", [$lock]);

            return 'stale';
        };
        expect(fn() => $cache->rememberLocked($prefix, 60, $calculate))->toThrow(RedisCommandFailedException::class)
            ->and($cache->exists($prefix))->toBeFalse()->and($cache->get($lock))->toBe('next-owner')
            ->and($cache->keys($prefix . ':pending:*'))->toBe([]);
        expect(fn() => $cache->rememberLocked($prefix, 60, static fn() => 'wrong', waitTimeout: 0.0))->toThrow(RedisCommandFailedException::class);
    });
});

it('computes one value for overlapping independent processes', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $processes = [];

        for ($index = 0; $index < 4; ++$index) {
            $processes[] = [proc_open([PHP_BINARY, __DIR__ . '/../Fixtures/cache-worker.php', $prefix, $cache->host, (string)$cache->port], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes), $pipes];
        }

        foreach ($processes as [$process, $pipes]) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            expect(proc_close($process))->toBe(0)->and($error)->toBe('')->and($output)->toBe('shared');
        }
        expect($cache->get($prefix . ':calculations'))->toBe('1');
    });
});

it('preserves a factory failure and still releases the lease if staging cleanup fails', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $writer = new class($prefix, $cache->host, $cache->port) extends RedisCache {

            public function del(string ...$keys): bool
            {
                if (str_contains($keys[0], ':pending:')) {
                    throw new LogicException('cleanup');
                }

                return parent::del(...$keys);
            }

        };
        expect(fn() => $writer->rememberLocked($prefix . ':failure', 60, static fn(): never => throw new LogicException('factory')))->toThrow(LogicException::class, 'factory');
        expect($cache->keys($prefix . ':lock:*'))->toBe([]);
    });
});

<?php
declare(strict_types=1);

use Raxos\Cache\Redis\RedisCache;
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
        expect(fn () => $cache->remember($prefix, 60, static fn () => throw new LogicException('factory')))->toThrow(LogicException::class);
        expect($cache->exists($prefix))->toBeFalse()->and($cache->remember($prefix, 60, static fn () => 'recovered'))->toBe('recovered');
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

<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Group\RedisKeys;
use Raxos\Cache\Redis\RedisCache;
use function RaxosTests\Cache\withRedisUnit;

covers(RedisKeys::class);

it('renames, dumps and restores values while retaining missing-key semantics', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        expect($cache->dump($key))->toBeNull()->and($cache->ttl($key))->toBe(-2)->and($cache->pttl($key))->toBe(-2);
        $cache->set($key, 'value');
        $dump = $cache->dump($key);
        expect($cache->restore($key . ':restored', 0, $dump))->toBeTrue()->and($cache->get($key . ':restored'))->toBe('value')
            ->and($cache->rename($key, $key . ':renamed'))->toBeTrue()->and($cache->exists($key))->toBeFalse()
            ->and($cache->renamenx($key . ':renamed', $key . ':restored'))->toBeFalse()
            ->and($cache->renamenx($key . ':renamed', $key . ':new'))->toBeTrue()
            ->and($cache->type($key . ':new'))->toBe(Redis::REDIS_STRING)
            ->and($cache->object('encoding', $key . ':new'))->toBeString();
        expect($cache->unlink($key . ':restored', $key . ':new'))->toBeTrue()->and($cache->unlink($key))->toBeFalse();
    });
});

it('expires keys at second and millisecond deadlines and touches multiple keys', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        $cache->mset([$key => 'a', $key . ':b' => 'b']);
        expect($cache->touch($key, $key . ':b'))->toBeTrue()->and($cache->touch($key . ':missing'))->toBeFalse()
            ->and($cache->expireAt($key, time() + 60))->toBeTrue()->and($cache->ttl($key))->toBeGreaterThan(50)
            ->and($cache->pexpire($key . ':b', 60_000))->toBeTrue()->and($cache->pttl($key . ':b'))->toBeGreaterThan(50_000)
            ->and($cache->pexpireat($key . ':b', (int)(microtime(true) * 1000) - 1))->toBeTrue()
            ->and($cache->exists($key . ':b'))->toBeFalse()->and($cache->wait(0, 1))->toBe(0);
    });
});

it('sorts set members numerically or lexicographically according to options', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        $cache->sadd($key, '2', '10', '1');
        expect($cache->sort($key))->toBe(['1', '2', '10'])->and($cache->sort($key, ['alpha' => true]))->toBe(['1', '10', '2']);
    });
});

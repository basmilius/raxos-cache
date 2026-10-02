<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Group\RedisStrings;
use Raxos\Cache\Redis\RedisCache;
use function RaxosTests\Cache\withRedisUnit;

covers(RedisStrings::class);

it('appends, slices, replaces and atomically exchanges stored strings', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        expect($cache->setnx($key, 'abc'))->toBeTrue()->and($cache->setnx($key, 'overwrite'))->toBeFalse()
            ->and($cache->append($key, 'def'))->toBe(6)->and($cache->strlen($key))->toBe(6)
            ->and($cache->getrange($key, 1, -2))->toBe('bcde')->and($cache->setrange($key, 2, 'XY'))->toBe(6)
            ->and($cache->getset($key, 'new'))->toBe('abXYef')->and($cache->get($key))->toBe('new');
    });
});

it('supports integer and floating point counter updates', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        expect($cache->decrby($key, 3))->toBe(-3)->and($cache->incrbyfloat($key, 1.5))->toBe(-1.5);
    });
});

it('preserves bit offsets and combines complete strings', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        expect($cache->setbit($key, 0, true))->toBe(0)->and($cache->getbit($key, 0))->toBe(1)
            ->and($cache->bitcount($key))->toBe(1)->and($cache->bitpos($key, 1))->toBe(0);
        $cache->set($key . ':other', "\x40");
        expect($cache->bitop('OR', $key . ':out', $key, $key . ':other'))->toBe(1)
            ->and($cache->get($key . ':out'))->toBe("\xc0");
    });
});

it('sets explicit second and millisecond expirations', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        expect($cache->psetex($key, 'ms', 60_000))->toBeTrue()->and($cache->pttl($key))->toBeGreaterThan(50_000)
            ->and($cache->setex($key, 'seconds', 60))->toBeTrue()->and($cache->get($key))->toBe('seconds');
    });
});

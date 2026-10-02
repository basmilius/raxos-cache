<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Group\RedisSets;
use Raxos\Cache\Redis\RedisCache;
use function RaxosTests\Cache\withRedisUnit;

covers(RedisSets::class);

it('stores set differences, intersections and unions without duplicate members', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        $cache->sadd($key . ':a', 'a', 'b');
        $cache->sadd($key . ':b', 'b', 'c');
        expect($cache->sdiffstore($key . ':out', $key . ':a', $key . ':b'))->toBe(1)
            ->and($cache->smembers($key . ':out'))->toBe(['a'])
            ->and($cache->sinterstore($key . ':out', $key . ':a', $key . ':b'))->toBe(1)
            ->and($cache->smembers($key . ':out'))->toBe(['b'])
            ->and($cache->sunionstore($key . ':out', $key . ':a', $key . ':b'))->toBe(3);
        expect($cache->sunion($key . ':a', $key . ':b'))->toEqualCanonicalizing(['a', 'b', 'c']);
        expect($cache->sdiffstore($key . ':out', $key . ':a', $key . ':a'))->toBeNull()
            ->and($cache->sinterstore($key . ':out', $key . ':a', $key . ':missing'))->toBeNull();
    });
});

it('moves and samples members using the selected count', function (): void {
    withRedisUnit(function (RedisCache $cache, string $key): void {
        $cache->sadd($key, 'only');
        expect($cache->smove($key, $key . ':out', 'only'))->toBeTrue()->and($cache->scard($key))->toBe(0)
            ->and($cache->srandmember($key . ':out', 1))->toBe(['only'])
            ->and($cache->spop($key . ':out', 1))->toBe(['only'])->and($cache->spop($key . ':out', 1))->toBe([]);
    });
});

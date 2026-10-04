<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Group\RedisPubSub;
use RaxosTests\Cache\UnitRedisCache;

covers(RedisPubSub::class);

it('forwards subscription patterns and preserves callback identity', function (): void {
    $callback = static function (Redis $connection, string $channel, string $payload): void {};
    $redis = test()->createMock(Redis::class);
    $redis->expects(test()->once())->method('psubscribe')->with(['unit:*'], $callback)->willReturn(true);
    $redis->expects(test()->once())->method('subscribe')->with(['unit'], $callback)->willReturn(true);
    $redis->expects(test()->once())->method('punsubscribe')->with(['unit:*'])->willReturn(true);
    $redis->expects(test()->once())->method('unsubscribe')->with([])->willReturn(true);
    $cache = new UnitRedisCache($redis);
    $cache->psubscribe(['unit:*'], $callback);
    $cache->subscribe(['unit'], $callback);
    $cache->punsubscribe(['unit:*']);
    $cache->unsubscribe();
});

it('returns publisher and introspection replies unchanged', function (): void {
    $redis = test()->createMock(Redis::class);
    $redis->expects(test()->once())->method('publish')->with('unit', 'payload')->willReturn(2);
    $redis->expects(test()->once())->method('pubsub')->with('NUMSUB', ['unit'])->willReturn(['unit' => 2]);
    $cache = new UnitRedisCache($redis);
    expect($cache->publish('unit', 'payload'))->toBe(2)->and($cache->pubsub('NUMSUB', ['unit']))->toBe(['unit' => 2]);
});

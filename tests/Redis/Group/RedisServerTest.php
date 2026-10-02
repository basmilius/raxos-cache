<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Group\RedisServer;
use RaxosTests\Cache\UnitRedisCache;

covers(RedisServer::class);

it('forwards scripts with keys before arguments and the exact key count', function (): void {
    $redis = test()->createMock(Redis::class);
    $redis->expects(test()->once())->method('eval')->with('return ARGV[1]', ['key-a', 'key-b', 'value', 3], 2)->willReturn('value');
    expect(new UnitRedisCache($redis)->eval('return ARGV[1]', ['key-a', 'key-b'], ['value', 3]))->toBe('value');
});

it('selects the correct flush command', function (): void {
    $redis = test()->createMock(Redis::class);
    $redis->expects(test()->once())->method('flushAll')->willReturn(true);
    $redis->expects(test()->once())->method('flushDB')->willReturn(true);
    $cache = new UnitRedisCache($redis);
    expect($cache->flushAll())->toBeTrue()->and($cache->flushDatabase())->toBeTrue();
});

<?php
declare(strict_types=1);

namespace RaxosTests\Cache;

use Raxos\Cache\Redis\RedisCache;
use Redis;
use function test;

final class UnitRedisCache extends RedisCache
{

    public function __construct(Redis $connection)
    {
        $this->connection = $connection;
        $this->prefix = 'unit';
        $this->host = '127.0.0.1';
        $this->port = 6379;
        $this->timeout = 0.0;
    }

}

function withRedisUnit(callable $test): void
{
    if (getenv('RAXOS_REDIS_HOST') === false) {
        test()->markTestSkipped('Set RAXOS_REDIS_HOST to run Redis tests.');
    }
    $prefix = 'raxos-unit:' . bin2hex(random_bytes(8));
    $cache = new RedisCache($prefix, getenv('RAXOS_REDIS_HOST'), (int)(getenv('RAXOS_REDIS_PORT') ?: 0));
    try {
        $test($cache, $prefix);
    } finally {
        $keys = $cache->keys($prefix . '*');
        if ($keys !== []) {
            $cache->del(...$keys);
        }
    }
}

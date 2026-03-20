<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Group;

use Raxos\Cache\Redis\RedisUtil;
use Raxos\Contract\Cache\RedisCacheExceptionInterface;
use Redis;
use function count;

/**
 * Trait RedisServer
 *
 * @property Redis $connection
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Group
 * @since 1.0.0
 */
trait RedisServer
{

    /**
     * Removes all information from all databases.
     *
     * @return bool
     * @throws RedisCacheExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::flushAll()
     */
    public function flushAll(): bool
    {
        return RedisUtil::wrap($this->connection->flushAll(...));
    }

    /**
     * Evaluates a Lua script on the server.
     *
     * @param string $script
     * @param array $keys
     * @param array $args
     *
     * @return mixed
     * @throws RedisCacheExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 2.1.0
     * @see Redis::eval()
     */
    public function eval(string $script, array $keys = [], array $args = []): mixed
    {
        return RedisUtil::wrap($this->connection->eval(...), $script, [...$keys, ...$args], count($keys));
    }

    /**
     * Removes all information from the current database.
     *
     * @return bool
     * @throws RedisCacheExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::flushDB()
     */
    public function flushDatabase(): bool
    {
        return RedisUtil::wrap($this->connection->flushDB(...));
    }

}

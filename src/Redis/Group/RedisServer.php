<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Group;

use Raxos\Cache\Redis\RedisUtil;
use Raxos\Contract\Cache\RedisCacheExceptionInterface;
use Redis;

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

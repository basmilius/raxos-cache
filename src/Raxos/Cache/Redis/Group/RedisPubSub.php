<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Group;

use Raxos\Cache\Redis\{RedisCacheException, RedisUtil};
use Redis;

/**
 * Trait RedisPubSub
 *
 * @property Redis $connection
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Group
 * @since 1.0.0
 */
trait RedisPubSub
{

    /**
     * Subscribe to channels that match the given patterns.
     *
     * @param string[] $patterns
     * @param callable $fn
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::psubscribe()
     */
    public function psubscribe(array $patterns, callable $fn): void
    {
        RedisUtil::wrap($this->connection->psubscribe(...), $patterns, $fn);
    }

    /**
     * Publish the given message to the subscribers of the given channel.
     *
     * @param string $channel
     * @param string $message
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::publish()
     */
    public function publish(string $channel, string $message): int
    {
        return RedisUtil::wrap($this->connection->publish(...), $channel, $message);
    }

    /**
     * Returns information about the Redis pub/sub system.
     *
     * @param string $keyword
     * @param string|array $argument
     *
     * @return array|int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::pubsub()
     */
    public function pubsub(string $keyword, string|array $argument): array|int
    {
        return RedisUtil::wrap($this->connection->pubsub(...), $keyword, $argument);
    }

    /**
     * Unsubscribe from channels that match the given patterns.
     *
     * @param array|null $patterns
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::punsubscribe()
     */
    public function punsubscribe(?array $patterns = null): void
    {
        RedisUtil::wrap($this->connection->punsubscribe(...), $patterns);
    }

    /**
     * Subscribe to the given channels.
     *
     * @param string[] $channels
     * @param callable $fn
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::subscribe()
     */
    public function subscribe(array $channels, callable $fn): void
    {
        RedisUtil::wrap($this->connection->subscribe(...), $channels, $fn);
    }

    /**
     * Unsubscribe from the given channels.
     *
     * @param array|null $channels
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::unsubscribe()
     */
    public function unsubscribe(?array $channels = null): void
    {
        RedisUtil::wrap($this->connection->unsubscribe(...), $channels);
    }

}

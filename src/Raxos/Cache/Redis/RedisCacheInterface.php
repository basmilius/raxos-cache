<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

/**
 * Interface RedisCacheInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.6
 */
interface RedisCacheInterface
{

    /**
     * Connects to the Redis server.
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function connect(): bool;

    /**
     * Gets the prefix used for the keys.
     *
     * @return string
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.6
     */
    public function getPrefix(): string;

    /**
     * Returns TRUE if we're connected to a Redis server.
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function isConnected(): bool;

    /**
     * Remembers data in our cache.
     *
     * @template T
     *
     * @param string $key
     * @param int $ttl
     * @param callable():T $fn
     *
     * @return T
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function remember(string $key, int $ttl, callable $fn): mixed;

    /**
     * Selects the given database.
     *
     * @param int $databaseId
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function selectDatabase(int $databaseId): void;

    /**
     * Gets a tagged cache instance.
     *
     * @param string[] $tags
     *
     * @return RedisTaggedCacheInterface
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function tags(array $tags): RedisTaggedCacheInterface;

}

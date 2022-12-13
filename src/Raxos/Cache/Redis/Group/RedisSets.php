<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Group;

use Raxos\Cache\Redis\{RedisCacheException, RedisUtil};
use Redis;

/**
 * Trait RedisSets
 *
 * @property Redis $connection
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Group
 * @since 1.0.0
 */
trait RedisSets
{

    /**
     * Adds the given members to the set at the specified key.
     *
     * @param string $key
     * @param string ...$members
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sAdd()
     * @noinspection SpellCheckingInspection
     */
    public function sadd(string $key, string ...$members): int
    {
        return RedisUtil::wrap($this->connection->sAdd(...), $key, ...$members);
    }

    /**
     * Gets the number of members in the set at the specified key.
     *
     * @param string $key
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sCard()
     * @noinspection SpellCheckingInspection
     */
    public function scard(string $key): int
    {
        return RedisUtil::wrap($this->connection->sCard(...), $key);
    }

    /**
     * Subtract the sets at the specified keys.
     *
     * @param string ...$keys
     *
     * @return string[]
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sDiff()
     * @noinspection SpellCheckingInspection
     */
    public function sdiff(string ...$keys): array
    {
        return RedisUtil::wrap($this->connection->sDiff(...), ...$keys);
    }

    /**
     * Subtract the sets at the specified keys and store the result in
     * the specified destination key.
     *
     * @param string $destination
     * @param string ...$keys
     *
     * @return int|null
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sDiffStore()
     * @noinspection SpellCheckingInspection
     */
    public function sdiffstore(string $destination, string ...$keys): ?int
    {
        return RedisUtil::wrap($this->connection->sDiffStore(...), $destination, ...$keys) ?: null;
    }

    /**
     * Intersect the sets at the specified keys.
     *
     * @param string ...$keys
     *
     * @return string[]
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sInter()
     */
    public function sinter(string ...$keys): array
    {
        return RedisUtil::wrap($this->connection->sInter(...), ...$keys);
    }

    /**
     * Intersect the sets at the specified keys and stores the result in
     * the specified destination key.
     *
     * @param string $destination
     * @param string ...$keys
     *
     * @return int|null
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sInterStore()
     * @noinspection SpellCheckingInspection
     */
    public function sinterstore(string $destination, string ...$keys): ?int
    {
        return RedisUtil::wrap($this->connection->sInterStore(...), $destination, ...$keys) ?: null;
    }

    /**
     * Determine if the given member is part of the set at the specified key.
     *
     * @param string $key
     * @param string $member
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sIsMember()
     * @noinspection SpellCheckingInspection
     */
    public function sismember(string $key, string $member): bool
    {
        return RedisUtil::wrap($this->connection->sIsMember(...), $key, $member);
    }

    /**
     * Gets all members in the set at the specified key.
     *
     * @param string $key
     *
     * @return string[]
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sMembers()
     * @noinspection SpellCheckingInspection
     */
    public function smembers(string $key): array
    {
        return RedisUtil::wrap($this->connection->sMembers(...), $key);
    }

    /**
     * Moves a member from the set at the given source to the set at the
     * given destination.
     *
     * @param string $source
     * @param string $destination
     * @param string $member
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sMove()
     * @noinspection SpellCheckingInspection
     */
    public function smove(string $source, string $destination, string $member): bool
    {
        return RedisUtil::wrap($this->connection->sMove(...), $source, $destination, $member);
    }

    /**
     * Removes and returns one or multiple random members from the set at
     * the specified key.
     *
     * @param string $key
     * @param int $count
     *
     * @return mixed
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sPop()
     * @noinspection SpellCheckingInspection
     */
    public function spop(string $key, int $count = 1): mixed
    {
        return RedisUtil::wrap($this->connection->sPop(...), $key, $count);
    }

    /**
     * Returns one or multiple random members from the set at the specified key.
     *
     * @param string $key
     * @param int $count
     *
     * @return mixed
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sRandMember()
     * @noinspection SpellCheckingInspection
     */
    public function srandmember(string $key, int $count = 1): mixed
    {
        return RedisUtil::wrap($this->connection->sRandMember(...), $key, $count);
    }

    /**
     * Removes the given members from the set at the specified key.
     *
     * @param string $key
     * @param string ...$members
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sRem()
     * @noinspection SpellCheckingInspection
     */
    public function srem(string $key, string ...$members): int
    {
        return RedisUtil::wrap($this->connection->sRem(...), $key, ...$members);
    }

    /**
     * Adds multiple sets.
     *
     * @param string ...$keys
     *
     * @return mixed
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sUnion()
     * @noinspection SpellCheckingInspection
     */
    public function sunion(string ...$keys): array
    {
        return RedisUtil::wrap($this->connection->sUnion(...), ...$keys);
    }

    /**
     * Adds multiple sets and stores the result in the specified destination key.
     *
     * @param string $destination
     * @param string ...$keys
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::sUnionStore()
     * @noinspection SpellCheckingInspection
     */
    public function sunionstore(string $destination, string ...$keys): int
    {
        return RedisUtil::wrap($this->connection->sUnionStore(...), $destination, ...$keys);
    }

}

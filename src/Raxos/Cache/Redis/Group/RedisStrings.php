<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Group;

use Raxos\Cache\Redis\{RedisCacheException, RedisUtil};
use Redis;

/**
 * Trait RedisStrings
 *
 * @property Redis $connection
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Group
 * @since 1.0.0
 */
trait RedisStrings
{

    /**
     * Appends the given value to the specified key.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::append()
     */
    public function append(string $key, mixed $value): int
    {
        return RedisUtil::wrap($this->connection->append(...), $key, $value);
    }

    /**
     * Counts the bits in the specified key.
     *
     * @param string $key
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::bitCount()
     * @noinspection SpellCheckingInspection
     */
    public function bitcount(string $key): int
    {
        return RedisUtil::wrap($this->connection->bitCount(...), $key);
    }

    /**
     * Performs bitwise operations between the specified keys.
     *
     * @param string $operation
     * @param string $destinationKey
     * @param string ...$keys
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::bitOp()
     * @noinspection SpellCheckingInspection
     */
    public function bitop(string $operation, string $destinationKey, string ...$keys): int
    {
        return RedisUtil::wrap($this->connection->bitOp(...), $operation, $destinationKey, ...$keys);
    }

    /**
     * Finds first bit set or clear in the specified key.
     *
     * @param string $key
     * @param int $bit
     * @param int $start
     * @param int|null $end
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::bitpos()
     * @noinspection SpellCheckingInspection
     */
    public function bitpos(string $key, int $bit, int $start = 0, ?int $end = null): int
    {
        return RedisUtil::wrap($this->connection->bitpos(...), $key, $bit, $start, $end);
    }

    /**
     * Decrements the integer value of the specified key.
     *
     * @param string $key
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::decr()
     */
    public function decr(string $key): int
    {
        return RedisUtil::wrap($this->connection->decr(...), $key);
    }

    /**
     * Decrements the integer value of the specified key by the given amount.
     *
     * @param string $key
     * @param int $amount
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::decrBy()
     * @noinspection SpellCheckingInspection
     */
    public function decrby(string $key, int $amount): int
    {
        return RedisUtil::wrap($this->connection->decrBy(...), $key, $amount);
    }

    /**
     * Gets the value of the specified key.
     *
     * @param string $key
     *
     * @return mixed
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::get()
     */
    public function get(string $key): mixed
    {
        return RedisUtil::wrap($this->connection->get(...), $key);
    }

    /**
     * Returns the bit value at the given offset in the string stored
     * at the specified key.
     *
     * @param string $key
     * @param int $offset
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::getBit()
     * @noinspection SpellCheckingInspection
     */
    public function getbit(string $key, int $offset): int
    {
        return RedisUtil::wrap($this->connection->getBit(...), $key, $offset);
    }

    /**
     * Gets a substring of the string stored at the specified key.
     *
     * @param string $key
     * @param int $start
     * @param int $end
     *
     * @return string
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::getRange()
     * @noinspection SpellCheckingInspection
     */
    public function getrange(string $key, int $start, int $end): string
    {
        return RedisUtil::wrap($this->connection->getRange(...), $key, $start, $end);
    }

    /**
     * Sets the value of the specified key and returns its old value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::getSet()
     * @noinspection SpellCheckingInspection
     */
    public function getset(string $key, mixed $value): mixed
    {
        return RedisUtil::wrap($this->connection->getSet(...), $key, $value);
    }

    /**
     * Increments the integer value of the specified key.
     *
     * @param string $key
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::incr()
     */
    public function incr(string $key): int
    {
        return RedisUtil::wrap($this->connection->incr(...), $key);
    }

    /**
     * Increments the integer value of the specified key with the given amount.
     *
     * @param string $key
     * @param int $amount
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::incrBy()
     * @noinspection SpellCheckingInspection
     */
    public function incrby(string $key, int $amount): int
    {
        return RedisUtil::wrap($this->connection->incrBy(...), $key, $amount);
    }

    /**
     * Increments the float value of the specified key with the given amount.
     *
     * @param string $key
     * @param float $amount
     *
     * @return float
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::incrByFloat()
     * @noinspection SpellCheckingInspection
     */
    public function incrbyfloat(string $key, float $amount): float
    {
        return RedisUtil::wrap($this->connection->incrByFloat(...), $key, $amount);
    }

    /**
     * Gets the values of the specified keys.
     *
     * @param string ...$keys
     *
     * @return array
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::mget()
     * @noinspection SpellCheckingInspection
     */
    public function mget(string ...$keys): array
    {
        return RedisUtil::wrap($this->connection->mget(...), $keys);
    }

    /**
     * Sets multiple keys to multiple values.
     *
     * @param array $sets
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::mset()
     * @noinspection SpellCheckingInspection
     */
    public function mset(array $sets): bool
    {
        return RedisUtil::wrap($this->connection->mset(...), $sets);
    }

    /**
     * Sets multiple keys to multiple values, but only if keys are not already stored.
     *
     * @param array $sets
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::msetnx()
     * @noinspection SpellCheckingInspection
     */
    public function msetnx(array $sets): bool
    {
        return RedisUtil::wrap($this->connection->msetnx(...), $sets) === 1;
    }

    /**
     * Sets the value and time to live (in milliseconds) of the specified key.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::psetex()
     * @noinspection SpellCheckingInspection
     */
    public function psetex(string $key, mixed $value, int $ttl): bool
    {
        return RedisUtil::wrap($this->connection->psetex(...), $key, $ttl, $value);
    }

    /**
     * Sets the value of the specified key.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::set()
     */
    public function set(string $key, mixed $value): bool
    {
        return RedisUtil::wrap($this->connection->set(...), $key, $value);
    }

    /**
     * Sets or clears the bit at offset in the string value stored at the specified key.
     *
     * @param string $key
     * @param int $offset
     * @param bool $value
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::setBit()
     * @noinspection SpellCheckingInspection
     */
    public function setbit(string $key, int $offset, bool $value): int
    {
        return RedisUtil::wrap($this->connection->setBit(...), $key, $offset, $value);
    }

    /**
     * Sets the value of the specified key and also sets the time to live.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::setex()
     * @noinspection SpellCheckingInspection
     */
    public function setex(string $key, mixed $value, int $ttl): bool
    {
        return RedisUtil::wrap($this->connection->setex(...), $key, $ttl, $value);
    }

    /**
     * Sets the value of the specified key, but only if the key doesn't exist.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::setnx()
     * @noinspection SpellCheckingInspection
     */
    public function setnx(string $key, mixed $value): bool
    {
        return RedisUtil::wrap($this->connection->setnx(...), $key, $value);
    }

    /**
     * Overwrites part of a string at the specified key starting at the specified offset.
     *
     * @param string $key
     * @param int $offset
     * @param string $value
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::setRange()
     * @noinspection SpellCheckingInspection
     */
    public function setrange(string $key, int $offset, string $value): int
    {
        return RedisUtil::wrap($this->connection->setRange(...), $key, $offset, $value);
    }

    /**
     * Gets the length of the value stored at the specified key.
     *
     * @param string $key
     *
     * @return int
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     * @see Redis::strlen()
     */
    public function strlen(string $key): int
    {
        return RedisUtil::wrap($this->connection->strlen(...), $key);
    }

}

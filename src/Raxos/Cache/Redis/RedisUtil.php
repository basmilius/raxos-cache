<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use RedisException;

/**
 * Class RedisUtil
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.6
 */
final class RedisUtil
{

    /**
     * Calls the given Redis function and when an {@see RedisException} is
     * thrown it is converted into a {@see RedisCacheException}.
     *
     * @template T
     *
     * @param callable():T $fn
     * @param mixed ...$arguments
     *
     * @return T
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.6
     */
    public static function wrap(callable $fn, mixed ...$arguments): mixed
    {
        try {
            return $fn(...$arguments);
        } catch (RedisException $err) {
            throw RedisCacheException::error($err);
        }
    }

}

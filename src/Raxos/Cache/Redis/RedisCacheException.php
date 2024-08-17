<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Error\CacheException;
use Raxos\Foundation\Error\ExceptionId;
use RedisException;
use function sprintf;

/**
 * Class RedisCacheException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.17
 */
final class RedisCacheException extends CacheException
{

    /**
     * Returns a command failed exception.
     *
     * @param string $command
     * @param string|null $message
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.17
     */
    public static function commandFailed(string $command, ?string $message = null): self
    {
        if ($message !== null) {
            return new self(
                ExceptionId::for(__METHOD__),
                'cache_redis_command_failed',
                sprintf('Command %s failed with message: %s', $command, $message)
            );
        }

        return new self(
            ExceptionId::for(__METHOD__),
            'cache_redis_command_failed',
            sprintf('Command %s failed without a message.', $command)
        );
    }

    /**
     * Returns a redis error exception.
     *
     * @param RedisException $err
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.17
     */
    public static function error(RedisException $err): self
    {
        return new self(
            ExceptionId::for(__METHOD__),
            'cache_redis_error',
            'Caught a Redis error.',
            $err
        );
    }

    /**
     * Returns a not installed exception.
     *
     * @return self
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.17
     */
    public static function notInstalled(): self
    {
        return new self(
            ExceptionId::for(__METHOD__),
            'cache_redis_not_installed',
            'Redis is not installed on the system.'
        );
    }

}

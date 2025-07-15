<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Error;

use Raxos\Foundation\Error\ExceptionId;
use RedisException;

/**
 * Class RedisErrorException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Error
 * @since 2.0.0
 */
final class RedisErrorException extends RedisCacheException
{

    /**
     * RedisErrorException constructor.
     *
     * @param RedisException $err
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public readonly RedisException $err
    )
    {
        parent::__construct(
            ExceptionId::guess(),
            'redis_error',
            'Caught a Redis error.',
            $err
        );
    }

}

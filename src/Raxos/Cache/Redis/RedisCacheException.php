<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Error\CacheException;

/**
 * Class RedisCacheException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.0
 */
final class RedisCacheException extends CacheException
{

    public const int ERR_DATABASE_SELECT_FAILED = 1;
    public const int ERR_INVALID_CALL = 2;
    public const int ERR_REDIS_EXCEPTION = 4;
    public const int ERR_REDIS_NOT_FOUND = 8;

}

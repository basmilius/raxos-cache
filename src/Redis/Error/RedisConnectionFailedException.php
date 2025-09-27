<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Error;

use Raxos\Contract\Cache\RedisCacheExceptionInterface;
use Raxos\Error\Exception;

/**
 * Class RedisConnectionFailedException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Error
 * @since 2.0.0
 */
final class RedisConnectionFailedException extends Exception implements RedisCacheExceptionInterface
{

    /**
     * RedisConnectionFailedException constructor.
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct()
    {
        parent::__construct(
            'redis_connection_failed',
            'Could not connect to Redis.'
        );
    }

}

<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Error;

use Raxos\Foundation\Error\ExceptionId;

/**
 * Class RedisImplementationMissingException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Error
 * @since 2.0.0
 */
final class RedisImplementationMissingException extends RedisCacheException
{

    /**
     * RedisImplementationMissingException constructor.
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct()
    {
        parent::__construct(
            ExceptionId::guess(),
            'redis_implementation_missing',
            'A Redis implementation is missing on the system.'
        );
    }

}

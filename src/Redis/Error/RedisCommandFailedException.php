<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Error;

use Raxos\Foundation\Error\ExceptionId;

/**
 * Class RedisCommandFailedException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Error
 * @since 2.0.0
 */
final class RedisCommandFailedException extends RedisCacheException
{

    /**
     * RedisCommandFailedException constructor.
     *
     * @param string $command
     * @param string|null $reason
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public readonly string $command,
        public readonly string|null $reason = null
    )
    {
        if ($this->reason !== null) {
            $message = "Command {$this->command} failed with reason: {$this->reason}";
        } else {
            $message = "Command {$this->command} failed without a reason.";
        }

        parent::__construct(
            ExceptionId::guess(),
            'redis_command_failed',
            $message
        );
    }

}

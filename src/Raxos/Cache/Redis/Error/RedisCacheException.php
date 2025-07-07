<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis\Error;

use Raxos\Cache\Error\CacheException;

/**
 * Class RedisCacheException
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis\Error
 * @since 2.0.0
 */
abstract class RedisCacheException extends CacheException {}

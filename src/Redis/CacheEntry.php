<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

/**
 * Class CacheEntry
 *
 * A lookup distinguishes a stored false or null from a missing key.
 *
 * @template T
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 3.3.0
 */
final readonly class CacheEntry
{
    /**
     * Preserves the distinction between a cache miss and a cached null or false value.
     *
     * @param bool $found
     * @param T $value
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    public function __construct(
        public bool $found,
        public mixed $value = null
    )
    {
    }
}

<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Redis\Error\RedisCommandFailedException;
use Raxos\Contract\Cache\RedisCacheExceptionInterface;
use Raxos\Contract\Cache\RedisCacheInterface;
use Raxos\Contract\Cache\RedisTaggedCacheInterface;
use ReflectionMethod;
use function array_map;
use function array_unshift;
use function bin2hex;
use function implode;
use function random_bytes;
use function sha1;

/**
 * Class RedisTaggedCache
 *
 * Publishes values with shared tag indexes and invalidates tags in bounded atomic batches.
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.0
 */
readonly class RedisTaggedCache implements RedisTaggedCacheInterface
{

    /**
     * Namespaces tag indexes independently from the keys stored in this cache.
     *
     * @var string
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public string $scope;

    /**
     * Allows atomic lookups only when subclass read overrides do not need the legacy path.
     *
     * @var bool
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    private bool $usesAtomicReads;

    /**
     * RedisTaggedCache constructor.
     *
     * @param RedisCacheInterface $redis
     * @param array $tags
     *
     * @throws RedisCacheExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function __construct(
        public RedisCacheInterface $redis,
        public array $tags
    )
    {
        if (empty($tags)) {
            throw new RedisCommandFailedException('TAGS', 'At least one tag should be provided.');
        }

        $this->scope = implode('|', $this->tags);
        $this->usesAtomicReads = $redis instanceof RedisCache
            && new ReflectionMethod($this, 'get')->getDeclaringClass()->getName() === self::class
            && new ReflectionMethod($this, 'exists')->getDeclaringClass()->getName() === self::class;
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function key(string $key): string
    {
        return $this->keyRaw(sha1($this->scope), $key);
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function del(string ...$keys): bool
    {
        $keys = array_map($this->key(...), $keys);

        return $this->redis->del(...$keys);
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function exists(string $key): bool
    {
        $key = $this->key($key);

        return $this->redis->exists($key);
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function flush(): void
    {
        foreach ($this->tags as $tag) {
            $tagKey = $this->keyRaw('tag', $tag, 'keys');

            // Pop and delete together so an interrupted flush cannot orphan an index entry.
            do {
                $removed = $this->redis->eval(
                    <<<'LUA'
                    local members = redis.call('SPOP', KEYS[1], 500)
                    if #members > 0 then
                        redis.call('DEL', unpack(members))
                    else
                        redis.call('DEL', KEYS[1])
                    end
                    return #members
                    LUA,
                    [$tagKey]
                );
            } while ($removed > 0);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function get(string $key): mixed
    {
        $key = $this->key($key);

        return $this->redis->get($key);
    }

    /**
     * Generates a raw key.
     *
     * @param string ...$parts
     *
     * @return string
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function keyRaw(string ...$parts): string
    {
        array_unshift($parts, $this->redis->getPrefix());

        return implode(':', $parts);
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function remember(
        string $key,
        int $ttl,
        callable $fn
    ): mixed
    {
        if ($this->usesAtomicReads ?? false) {
            $entry = $this->redis->lookup($this->key($key));

            if ($entry->found) {
                return $entry->value;
            }
        } elseif ($this->exists($key)) {
            return $this->get($key);
        }

        $this->set($key, $value = $fn(), $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function set(
        string $key,
        mixed $value,
        int $ttl
    ): bool
    {
        if ($ttl <= 0) {
            throw new RedisCommandFailedException('SETEX', 'The TTL must be positive.');
        }

        $key = $this->key($key);
        $pending = $key . ':pending:' . bin2hex(random_bytes(16));

        // Stage through setex so existing serializer overrides remain active.
        try {
            if (!$this->redis->setex($pending, $value, $ttl)) {
                return false;
            }

            return (bool)$this->redis->eval(
                <<<'LUA'
                for i = 3, #KEYS do
                    local kind = redis.call('TYPE', KEYS[i]).ok
                    if kind ~= 'none' and kind ~= 'set' then
                        return redis.error_reply('Tag index must be a set')
                    end
                end
                if redis.call('EXISTS', KEYS[1]) == 0 then return 0 end
                redis.call('RENAME', KEYS[1], KEYS[2])
                redis.call('EXPIRE', KEYS[2], ARGV[1])
                for i = 3, #KEYS do
                    local remaining = redis.call('PTTL', KEYS[i])
                    redis.call('SADD', KEYS[i], KEYS[2])
                    local ttl = tonumber(ARGV[1]) * 1000
                    if remaining == -2 or (remaining >= 0 and remaining < ttl) then
                        redis.call('PEXPIRE', KEYS[i], ttl)
                    end
                end
                return 1
                LUA,
                [$pending, $key, ...array_map(fn(string $tag): string => $this->keyRaw('tag', $tag, 'keys'), $this->tags)],
                [$ttl]
            );
        } finally {
            $this->redis->del($pending);
        }
    }

}

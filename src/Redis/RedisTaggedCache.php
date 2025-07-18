<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Redis\Contract\{RedisCacheInterface, RedisTaggedCacheInterface};
use Raxos\Cache\Redis\Error\{RedisCacheException, RedisCommandFailedException};
use function array_map;
use function array_merge;
use function array_unshift;
use function implode;
use function max;
use function sha1;

/**
 * Class RedisTaggedCache
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.0
 */
readonly class RedisTaggedCache implements RedisTaggedCacheInterface
{

    public string $scope;

    /**
     * RedisTaggedCache constructor.
     *
     * @param RedisCache $redis
     * @param array $tags
     *
     * @throws RedisCacheException
     *
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
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function key(string $key): string
    {
        return $this->keyRaw(sha1($this->scope), $key);
    }

    /**
     * {@inheritdoc}
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
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function flush(): void
    {
        $remove = array_merge(...array_map(function (string $tag): array {
            $tagKey = $this->keyRaw('tag', $tag, 'keys');
            $members = $this->redis->smembers($tagKey);
            $members[] = $tagKey;

            return $members;
        }, $this->tags));

        foreach ($remove as $key) {
            $this->redis->del($key);
        }
    }

    /**
     * {@inheritdoc}
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
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function remember(string $key, int $ttl, callable $fn): mixed
    {
        if ($this->exists($key)) {
            return $this->get($key);
        }

        $this->set($key, $value = $fn(), $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function set(string $key, mixed $value, int $ttl): bool
    {
        $this->linkTags($key = $this->key($key), $ttl);

        return $this->redis->setex($key, $value, $ttl);
    }

    /**
     * Links the tags to the given key.
     *
     * @param string $key
     * @param int $ttl
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    private function linkTags(string $key, int $ttl): void
    {
        foreach ($this->tags as $tag) {
            $tagKey = $this->keyRaw('tag', $tag, 'keys');
            $setTtl = max($this->redis->ttl($tagKey), $ttl);

            if ($setTtl < 0) {
                $setTtl = null;
            }

            $this->redis->sadd($tagKey, $key);
            $this->redis->expire($tagKey, $setTtl);
        }
    }

}

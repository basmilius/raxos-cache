<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Redis\Group\{RedisKeys, RedisPubSub, RedisServer, RedisSets, RedisStrings};
use Redis;
use function class_exists;
use function sprintf;

/**
 * Class RedisCache
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Cache\Redis
 * @since 1.0.0
 */
class RedisCache implements RedisCacheInterface
{

    use RedisKeys;
    use RedisPubSub;
    use RedisServer;
    use RedisSets;
    use RedisStrings;

    protected readonly Redis $connection;

    /**
     * RedisCache constructor.
     *
     * @param string $prefix
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param bool $connect
     *
     * @throws RedisCacheException
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function __construct(
        public readonly string $prefix,
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 6379,
        public readonly float $timeout = 0.0,
        bool $connect = true
    )
    {
        if (!class_exists(Redis::class)) {
            throw new RedisCacheException('Redis is not installed on the webserver.', RedisCacheException::ERR_REDIS_NOT_FOUND);
        }

        $this->connection = new Redis();

        if ($connect) {
            $this->connect();
        }
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function connect(): bool
    {
        return RedisUtil::wrap($this->connection->connect(...), $this->host, $this->port, $this->timeout);
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.6
     */
    public final function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public final function isConnected(): bool
    {
        return RedisUtil::wrap($this->connection->isConnected(...));
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

        $this->setex($key, $value = $fn(), $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function selectDatabase(int $databaseId): void
    {
        if (RedisUtil::wrap($this->connection->select(...), $databaseId) === false) {
            throw new RedisCacheException(sprintf('Could not select database with id %d.', $databaseId), RedisCacheException::ERR_DATABASE_SELECT_FAILED);
        }
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function tags(array $tags): RedisTaggedCacheInterface
    {
        return new RedisTaggedCache($this, $tags);
    }

}

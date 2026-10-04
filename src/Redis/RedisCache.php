<?php
declare(strict_types=1);

namespace Raxos\Cache\Redis;

use Raxos\Cache\Redis\Error\RedisCommandFailedException;
use Raxos\Cache\Redis\Error\RedisConnectionFailedException;
use Raxos\Cache\Redis\Error\RedisImplementationMissingException;
use Raxos\Cache\Redis\Group\RedisKeys;
use Raxos\Cache\Redis\Group\RedisPubSub;
use Raxos\Cache\Redis\Group\RedisServer;
use Raxos\Cache\Redis\Group\RedisSets;
use Raxos\Cache\Redis\Group\RedisStrings;
use Raxos\Contract\Cache\RedisCacheExceptionInterface;
use Raxos\Contract\Cache\RedisCacheInterface;
use Raxos\Contract\Cache\RedisTaggedCacheInterface;
use Redis;
use RedisException;
use ReflectionMethod;
use Throwable;
use function bin2hex;
use function class_exists;
use function hrtime;
use function is_finite;
use function random_bytes;
use function sprintf;
use function usleep;

/**
 * Class RedisCache
 *
 * Provides Redis commands and cached computation using the configured native serializer.
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

    /**
     * Retains the connection used by this object for its entire lifetime.
     *
     * @var Redis
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    protected readonly Redis $connection;

    /**
     * Keeps serializer overrides on their original read path without reflecting on every cache hit.
     *
     * @var bool|null
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    private ?bool $usesNativeReads = null;

    /**
     * RedisCache constructor.
     *
     * @param string $prefix
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param bool $connect
     *
     * @throws RedisCacheExceptionInterface
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
        $this->usesNativeReads = new ReflectionMethod($this, 'get')->getDeclaringClass()->getName() === self::class;

        if (!class_exists(Redis::class)) {
            throw new RedisImplementationMissingException();
        }

        try {
            $this->connection = new Redis();

            if ($connect) {
                $this->connect();
            }
        } catch (RedisException) {
            throw new RedisConnectionFailedException();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function connect(): bool
    {
        return RedisUtil::wrap($this->connection->connect(...), $this->host, $this->port, $this->timeout);
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.6
     */
    public final function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public final function isConnected(): bool
    {
        return RedisUtil::wrap($this->connection->isConnected(...));
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
        $entry = $this->lookup($key);

        if ($entry->found) {
            return $entry->value;
        }

        $this->setex($key, $value = $fn(), $ttl);

        return $value;
    }

    /**
     * Reads the native value and existence in one atomic snapshot. Custom get overrides use their existing read path.
     *
     * @param string $key
     *
     * @return CacheEntry<mixed>
     * @throws RedisCacheExceptionInterface
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    public function lookup(string $key): CacheEntry
    {
        $this->usesNativeReads ??= new ReflectionMethod($this, 'get')->getDeclaringClass()->getName() === self::class;

        if (!$this->usesNativeReads) {
            return $this->exists($key) ? new CacheEntry(true, $this->get($key)) : new CacheEntry(false);
        }

        $snapshot = $this->eval(
            "local value = redis.call('GET', KEYS[1]); if value == false then return {0} end; return {1, value}",
            [$key]
        );

        return $snapshot[0] === 1
            ? new CacheEntry(true, RedisUtil::wrap($this->connection->_unpack(...), $snapshot[1]))
            : new CacheEntry(false);
    }

    /**
     * Waits up to waitTimeout seconds for a lock with a lockTtl-second lease. An expired owner cannot publish.
     *
     * @template T
     * @param string $key
     * @param int $ttl
     * @param callable():T $fn
     * @param int $lockTtl
     * @param float $waitTimeout
     *
     * @return T
     * @throws RedisCacheExceptionInterface|Throwable
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    public function rememberLocked(
        string $key,
        int $ttl,
        callable $fn,
        int $lockTtl = 30,
        float $waitTimeout = 2.0
    ): mixed
    {
        if ($ttl <= 0 || $lockTtl <= 0 || $waitTimeout < 0 || !is_finite($waitTimeout)) {
            throw new RedisCommandFailedException('LOCK', 'Expiry must be positive and waitTimeout must be finite and non-negative.');
        }

        $owner = bin2hex(random_bytes(16));
        $lock = $this->prefix . ':lock:' . hash('sha256', $key);
        $deadline = hrtime(true) + $waitTimeout * 1_000_000_000;

        do {
            $entry = $this->lookup($key);

            if ($entry->found) {
                return $entry->value;
            }

            $acquired = $this->eval("return redis.call('SET', KEYS[1], ARGV[1], 'NX', 'EX', ARGV[2]) and 1 or 0", [$lock], [$owner, $lockTtl]);

            if ($acquired === 1) {
                return $this->computeWithLock($key, $ttl, $fn, $lock, $owner);
            }

            if (hrtime(true) >= $deadline) {
                throw new RedisCommandFailedException('LOCK', 'Timed out waiting for the cache lock.');
            }

            usleep((int)min(10_000, max(1, ($deadline - hrtime(true)) / 1000)));
        } while (true);
    }

    /**
     * Publishes only while this owner holds the lease; cleanup never replaces a factory failure.
     *
     * @template T
     * @param string $key
     * @param int $ttl
     * @param callable():T $fn
     * @param string $lock
     * @param string $owner
     *
     * @return T
     * @throws RedisCacheExceptionInterface|Throwable
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    private function computeWithLock(
        string $key,
        int $ttl,
        callable $fn,
        string $lock,
        string $owner
    ): mixed
    {
        $pending = $this->prefix . ':pending:' . $owner;
        $failure = null;

        try {
            $entry = $this->lookup($key);

            if ($entry->found) {
                return $entry->value;
            }

            $value = $fn();

            if (!$this->setex($pending, $value, $ttl)) {
                throw new RedisCommandFailedException('SETEX', 'Could not stage the cache value.');
            }

            $published = $this->eval(
                <<<'LUA'
                if redis.call('GET', KEYS[1]) ~= ARGV[1] then return 0 end
                if redis.call('EXISTS', KEYS[2]) == 0 then return 0 end
                redis.call('RENAME', KEYS[2], KEYS[3])
                redis.call('EXPIRE', KEYS[3], ARGV[2])
                return 1
                LUA,
                [$lock, $pending, $key],
                [$owner, $ttl]
            );

            if ($published !== 1) {
                throw new RedisCommandFailedException('LOCK', 'The cache lock lease expired before publication.');
            }

            return $value;
        } catch (Throwable $error) {
            $failure = $error;

            throw $error;
        } finally {
            try {
                $this->releaseLock($pending, $lock, $owner);
            } catch (Throwable $cleanupError) {
                if ($failure === null) {
                    throw $cleanupError;
                }
            }
        }
    }

    /**
     * Attempts both staging cleanup and owner-checked release even when one operation fails.
     *
     * @param string $pending
     * @param string $lock
     * @param string $owner
     *
     * @return void
     * @throws RedisCacheExceptionInterface|Throwable
     * @author Bas Milius <bas@mili.us>
     * @since 3.3.0
     */
    private function releaseLock(
        string $pending,
        string $lock,
        string $owner
    ): void
    {
        $failure = null;

        try {
            $this->del($pending);
        } catch (Throwable $error) {
            $failure = $error;
        }

        try {
            $this->eval(
                "if redis.call('GET', KEYS[1]) == ARGV[1] then return redis.call('DEL', KEYS[1]) end; return 0",
                [$lock],
                [$owner]
            );
        } catch (Throwable $error) {
            $failure ??= $error;
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function selectDatabase(int $databaseId): void
    {
        if (RedisUtil::wrap($this->connection->select(...), $databaseId) === false) {
            throw new RedisCommandFailedException('SELECT', sprintf('Could not select database with id %d.', $databaseId));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function tags(array $tags): RedisTaggedCacheInterface
    {
        return new RedisTaggedCache($this, $tags);
    }

}

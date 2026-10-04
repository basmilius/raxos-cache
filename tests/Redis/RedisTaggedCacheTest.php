<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Error\RedisCommandFailedException;
use Raxos\Cache\Redis\Error\RedisErrorException;
use Raxos\Cache\Redis\RedisCache;
use Raxos\Cache\Redis\RedisTaggedCache;
use function RaxosTests\Cache\withRedisUnit;

covers(RedisTaggedCache::class);

it('rejects empty tag sets before issuing commands', function (): void {
    expect(fn() => new RedisCache('unit', connect: false)->tags([]))->toThrow(RedisCommandFailedException::class);
});

it('builds stable scoped keys and preserves longer index expiry', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $tagged = $cache->tags(['merchant', 'orders']);
        expect($tagged->scope)->toBe('merchant|orders')->and($tagged->key('id'))->toBe($prefix . ':' . sha1('merchant|orders') . ':id')
            ->and($tagged->keyRaw('tag', 'orders', 'keys'))->toBe($prefix . ':tag:orders:keys');
        $calls = 0;
        $factory = static function () use (&$calls): string {
            ++$calls;

            return '0';
        };
        expect($tagged->remember('long', 120, $factory))->toBe('0')->and($tagged->remember('long', 120, $factory))->toBe('0')->and($calls)->toBe(1);
        $tagged->set('short', 'short', 10);
        expect($cache->ttl($tagged->keyRaw('tag', 'orders', 'keys')))->toBeGreaterThan(100)
            ->and($cache->smembers($tagged->keyRaw('tag', 'orders', 'keys')))->toContain($tagged->key('long'), $tagged->key('short'));
        expect($tagged->del('long', 'short'))->toBeTrue()->and($tagged->get('long'))->toBeFalse();
        $tagged->flush();
        expect($cache->exists($tagged->keyRaw('tag', 'orders', 'keys')))->toBeFalse();
    });
});

it('publishes serializer output atomically with membership after an overlapping flush', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        $other = new RedisCache($prefix, $cache->host, $cache->port);
        $writer = new class($prefix, $cache->host, $cache->port) extends RedisCache {

            public ?Closure $afterStage = null;

            public function setex(string $key, mixed $value, int $ttl): bool
            {
                $result = parent::setex($key, json_encode($value, JSON_THROW_ON_ERROR), $ttl);
                ($this->afterStage ?? static fn() => null)();

                return $result;
            }

            public function get(string $key): mixed
            {
                $value = parent::get($key);

                return $value === false ? false : json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            }

        };
        $writer->afterStage = static fn() => $other->tags(['catalog'])->flush();
        $tagged = $writer->tags(['catalog']);
        expect($tagged->set('items', ['name' => 'Keyboard'], 60))->toBeTrue()
            ->and($tagged->get('items'))->toBe(['name' => 'Keyboard'])
            ->and($cache->smembers($tagged->keyRaw('tag', 'catalog', 'keys')))->toContain($tagged->key('items'));
        $other->tags(['catalog'])->flush();
        expect($tagged->get('items'))->toBeFalse()->and($cache->keys($prefix . '*pending*'))->toBe([]);
    });
});

it('keeps permanent indexes permanent and validates index types before replacing values', function (): void {
    withRedisUnit(function (RedisCache $cache): void {
        $tagged = $cache->tags(['catalog']);
        $index = $tagged->keyRaw('tag', 'catalog', 'keys');
        $cache->sadd($index, 'existing');
        $tagged->set('item', 'before', 100);
        expect($cache->ttl($index))->toBe(-1);
        $cache->del($index);
        $cache->set($index, 'invalid');
        expect(fn() => $tagged->set('item', 'after', 10))->toThrow(RedisErrorException::class);
        expect($tagged->get('item'))->toBe('before');
    });
});

it('rejects non-positive expiry without creating index or value', function (): void {
    withRedisUnit(function (RedisCache $cache, string $prefix): void {
        expect(fn() => $cache->tags(['catalog'])->set('items', 'value', 0))->toThrow(RedisCommandFailedException::class);
        expect($cache->keys($prefix . '*'))->toBe([]);
    });
});

it('preserves a tagged subclass read transformation when remembering a native cache hit', function (): void {
    withRedisUnit(function (RedisCache $cache): void {
        $tagged = new readonly class($cache, ['catalog']) extends RedisTaggedCache {
            public function get(string $key): mixed
            {
                return strtoupper(parent::get($key));
            }
        };
        $tagged->set('item', 'stored value', 60);

        expect($tagged->remember('item', 60, static fn(): never => throw new LogicException('The cached value must be reused.')))
            ->toBe('STORED VALUE');
    });
});

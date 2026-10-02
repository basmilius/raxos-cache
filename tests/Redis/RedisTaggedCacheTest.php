<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Error\RedisCommandFailedException;
use Raxos\Cache\Redis\{RedisCache, RedisTaggedCache};
use function RaxosTests\Cache\withRedisUnit;

covers(RedisTaggedCache::class);

it('rejects empty tag sets before issuing commands', function (): void {
    expect(fn () => new RedisCache('unit', connect: false)->tags([]))->toThrow(RedisCommandFailedException::class);
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

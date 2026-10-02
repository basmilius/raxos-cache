<?php
declare(strict_types=1);

use Raxos\Cache\Redis\RedisCache;
use RaxosTests\Cache\RecordingRedisCache;

beforeEach(function (): void {
    if (getenv('RAXOS_REDIS_HOST') === false) {
        $this->markTestSkipped('Set RAXOS_REDIS_HOST to run Redis integration tests.');
    }
    $this->prefix = 'raxos-test:' . bin2hex(random_bytes(8));
    $this->redis = new RecordingRedisCache($this->prefix, getenv('RAXOS_REDIS_HOST'), (int)(getenv('RAXOS_REDIS_PORT') ?: 0));
});

afterEach(function (): void {
    if (!isset($this->redis)) {
        return;
    }
    $keys = $this->redis->keys($this->prefix . '*');
    if ($keys !== []) {
        $this->redis->del(...$keys);
    }
});

it('normalizes native Redis boolean replies', function (): void {
    $key = $this->prefix . ':native';
    expect($this->redis->msetnx([$key => 'value']))->toBeTrue();
    expect($this->redis->msetnx([$key => 'replacement']))->toBeFalse();
    expect($this->redis->expire($key, 60))->toBe(1);
    expect($this->redis->get($key))->toBe('value');
});

it('remembers tagged entries and invalidates all overlapping scopes in bounded batches', function (): void {
    $first = $this->redis->tags(['orders']);
    $second = $this->redis->tags(['orders', 'merchant']);
    $unrelated = $this->redis->tags(['other']);
    $unrelated->set('keep', 'safe', 60);
    for ($i = 0; $i < 1_205; $i++) {
        $first->set((string)$i, 'value', 60);
    }
    $second->set('shared', 'value', 60);
    expect($first->remember('0', 60, static fn() => throw new RuntimeException('Must use cache.')))->toBe('value');
    $first->flush();
    expect($first->exists('0'))->toBeFalse();
    expect($first->exists('1204'))->toBeFalse();
    expect($second->exists('shared'))->toBeFalse();
    expect($unrelated->get('keep'))->toBe('safe');
    expect($this->redis->batches)->toBe([500, 500, 206, 0]);
    $first->set('fresh', 'new', 60);
    expect($first->get('fresh'))->toBe('new');
});

it('retains cached zero and false values without invoking the factory twice', function (): void {
    $key = $this->prefix . ':remember';
    $calls = 0;
    $factory = static function () use (&$calls): string {
        ++$calls;
        return '0';
    };
    expect($this->redis->remember($key, 60, $factory))->toBe('0')
        ->and($this->redis->remember($key, 60, $factory))->toBe('0')
        ->and($calls)->toBe(1)->and($this->redis->ttl($key))->toBeGreaterThan(0);
});

it('wraps counters, multi-key writes and expiry consistently', function (): void {
    $first = $this->prefix . ':first';
    $second = $this->prefix . ':second';
    expect($this->redis->mset([$first => '0', $second => '2']))->toBeTrue()
        ->and($this->redis->mget($first, $second))->toBe(['0', '2'])
        ->and($this->redis->incr($first))->toBe(1)
        ->and($this->redis->incrby($first, 4))->toBe(5)
        ->and($this->redis->decr($first))->toBe(4)
        ->and($this->redis->expire($first, 60))->toBe(1)
        ->and($this->redis->persist($first))->toBeTrue()
        ->and($this->redis->ttl($first))->toBe(-1)
        ->and($this->redis->expire($second, 0))->toBe(1)
        ->and($this->redis->exists($second))->toBeFalse();
});

it('uses native set operations without mixing unrelated keys', function (): void {
    $first = $this->prefix . ':set-a';
    $second = $this->prefix . ':set-b';
    expect($this->redis->sadd($first, 'a', 'b', 'b'))->toBe(2)
        ->and($this->redis->sadd($second, 'b', 'c'))->toBe(2)
        ->and($this->redis->scard($first))->toBe(2)
        ->and($this->redis->sismember($first, 'a'))->toBeTrue()
        ->and($this->redis->sinter($first, $second))->toBe(['b'])
        ->and($this->redis->sdiff($first, $second))->toBe(['a'])
        ->and($this->redis->srem($first, 'a'))->toBe(1);
});

it('isolates tagged values with identical logical keys', function (): void {
    $a = $this->redis->tags(['a']);
    $b = $this->redis->tags(['b']);
    $a->set('same', 'first', 60);
    $b->set('same', 'second', 60);
    expect($a->get('same'))->toBe('first')->and($b->get('same'))->toBe('second');
    $a->del('same');
    expect($a->exists('same'))->toBeFalse()->and($b->get('same'))->toBe('second');
    $a->flush();
    $a->flush();
    expect($b->get('same'))->toBe('second');
});

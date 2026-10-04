<?php
declare(strict_types=1);

use Raxos\Cache\Redis\Error\RedisErrorException;
use Raxos\Cache\Redis\RedisUtil;

covers(RedisUtil::class);

it('preserves arguments and falsey command results', function (mixed $value): void {
    expect(RedisUtil::wrap(static fn(string $key, mixed $result): mixed => [$key, $result], 'key', $value))->toBe(['key', $value]);
})->with([[null], [false], [0], [''], [[]], ['value']]);

it('wraps native Redis failures and preserves their cause', function (): void {
    $cause = new RedisException('unit failure');
    try {
        RedisUtil::wrap(static fn() => throw $cause);
        test()->fail('The native failure must be wrapped.');
    } catch (RedisErrorException $error) {
        expect($error->getPrevious())->toBe($cause)->and($error->getPrevious()->getMessage())->toBe('unit failure');
    }
});

it('lets unrelated exceptions retain their identity', function (): void {
    $error = new LogicException('factory');
    expect(fn() => RedisUtil::wrap(static fn() => throw $error))->toThrow($error);
});

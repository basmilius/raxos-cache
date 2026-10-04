<?php
declare(strict_types=1);

namespace RaxosTests\Cache;

use Raxos\Cache\Redis\RedisCache;

final class RecordingRedisCache extends RedisCache
{
    public array $batches = [];

    public function eval(string $script, array $keys = [], array $args = []): mixed
    {
        $result = parent::eval($script, $keys, $args);

        if (str_contains($script, 'SPOP')) {
            $this->batches[] = $result;
        }

        return $result;
    }
}

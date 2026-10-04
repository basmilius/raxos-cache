<?php
declare(strict_types=1);

use Raxos\Cache\Redis\RedisCache;

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return;
}

$local = dirname(__DIR__, 2) . '/vendor/autoload.php';
require is_file($local) ? $local : dirname(__DIR__, 3) . '/vendor/autoload.php';

$cache = new RedisCache($argv[1], $argv[2], (int)$argv[3]);
echo $cache->rememberLocked($argv[1] . ':shared', 60, static function () use ($cache, $argv): string {
    $cache->incr($argv[1] . ':calculations');
    usleep(150_000);

    return 'shared';
});

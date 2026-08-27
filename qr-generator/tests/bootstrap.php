<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// A throw-away SQLite database, rebuilt from the migrations on every run so the
// tests exercise the same schema as production.
(new Filesystem())->remove(dirname(__DIR__).'/var/data_test.db');

$run = static function (array $command): void {
    (new Process($command, dirname(__DIR__), ['APP_ENV' => 'test'] + $_SERVER))->mustRun();
};

$run(['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction', '--quiet']);

// The rate limiter keeps its counters in the cache, which outlives a test run:
// reset it so two runs in a row do not throttle each other.
$run(['php', 'bin/console', 'cache:pool:clear', 'cache.rate_limiter', '--quiet']);

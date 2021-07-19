<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Container\Logger;
use App\Grpc;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
define("APP_DEBUG", $_ENV['APP_DEBUG'] !== 'false' && $_ENV['APP_DEBUG']);

Swoole\Coroutine\run(function () {
    $grpc = Grpc::new();
    $server = new Swoole\Coroutine\Http\Server('0.0.0.0', 9502, false, false);
    $init = function () {
        App\Container\DB::enableCoroutine();
        App\Container\RDS::enableCoroutine();
    };
    $server->handle('/', $grpc->handler($init));
    $server->set([
        'open_http2_protocol' => true,
        'http_compression' => false,
    ]);

    foreach ([SIGHUP, SIGINT, SIGTERM] as $signal) {
        Swoole\Process::signal($signal, function () use ($server) {
            Logger::instance()->info('Shutdown swoole coroutine server');
            $server->shutdown();
        });
    }

    Logger::instance()->info('Start swoole coroutine server');
    $server->start();
});

<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Container\Logger;
use App\Grpc;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
define("APP_DEBUG", $_ENV['APP_DEBUG'] !== 'false' && $_ENV['APP_DEBUG']);

$grpc = Grpc::new();
$http = new Swoole\Http\Server('0.0.0.0', 9501);
$init = function () {
    App\Container\DB::enableCoroutine();
    App\Container\RDS::enableCoroutine();
};
$http->on('Request', $grpc->handler($init));
$http->set([
    'enable_coroutine' => true,
    'worker_num' => 4,
    'open_http2_protocol' => true,
    'http_compression' => false,
]);
Logger::instance()->info('Start swoole server');
$http->start();

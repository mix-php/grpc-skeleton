<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Container\Logger;
use App\Grpc;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
define("APP_DEBUG", $_ENV['APP_DEBUG'] !== 'false' && $_ENV['APP_DEBUG']);

/**
 * 多进程默认开启了协程
 * 关闭协程只需关闭 `enable_coroutine` 配置并注释数据库的 `::enableCoroutine()` 即可退化为多进程同步模式
 */

$grpc = Grpc::new();
$http = new Swoole\Http\Server('0.0.0.0', 9501);
$http->on('Request', $grpc->handler());
$http->on('WorkerStart', function ($server, $workerId) {
    App\Container\DB::enableCoroutine();
    App\Container\RDS::enableCoroutine();
});
$http->set([
    'enable_coroutine' => true,
    'worker_num' => 4,
    'open_http2_protocol' => true,
    'http_compression' => false,
]);
Logger::instance()->info('Start swoole server');
$http->start();

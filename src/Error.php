<?php

namespace App;

use App\Container\Logger;

/**
 * Class Error
 * @package App
 */
class Error
{

    public static function register(): void
    {
        $error = new Error();
        set_error_handler([$error, 'error']);
        set_exception_handler([$error, 'exception']); // swoole 协程不支持该函数
        register_shutdown_function([$error, 'shutdown']);
    }

    /**
     * @param $errno
     * @param $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function error($errno, $errstr, $errfile = '', $errline = 0): void
    {
        if (error_reporting() & $errno) {
            // 委托给异常处理
            $isFatalWarning = function ($errno, $errstr) {
                if ($errno == E_WARNING && strpos($errstr, 'require') === 0) {
                    return true;
                }
                return false;
            };
            if ($isFatalWarning($errno, $errstr)) {
                $this->exception(new \Error(sprintf('%s in %s on line %d', $errstr, $errfile, $errline), $errno));
                return;
            }
            // 转换为异常抛出
            throw new \Error(sprintf('%s in %s on line %d', $errstr, $errfile, $errline), $errno);
        }
    }

    public function shutdown(): void
    {
        $isFatal = function ($errno) {
            return in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
        };
        if (!is_null($error = error_get_last()) && $isFatal($error['type'])) {
            // 委托给异常处理
            $this->exception(new \Error(sprintf('%s in %s on line %d', $error['message'], $error['file'], $error['line']), $error['type']));
        }
    }

    /**
     * @param \Throwable $ex
     */
    public function exception(\Throwable $ex): void
    {
        Logger::instance()->error(sprintf('%s in %s on line %d', $ex->getMessage(), $ex->getFile(), $ex->getLine()));
    }

}

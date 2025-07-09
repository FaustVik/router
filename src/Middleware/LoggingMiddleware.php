<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    private string $logFile;

    public function __construct(string $logFile = 'router.log')
    {
        $this->logFile = $logFile;
    }//end __construct()

    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getUri();
        $userAgent = $request->getHeader('User-Agent', 'Unknown');
        $ip = $request->getServerParam('REMOTE_ADDR', 'Unknown');

        // Выполняем следующий middleware
        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
// в миллисекундах
        $statusCode = $response->getStatusCode();

        // Формируем лог сообщение
        $logMessage = sprintf(
            "[%s] %s %s %s %s - %d - %s ms - %s\n",
            date('Y-m-d H:i:s'),
            $ip,
            $method,
            $uri,
            $userAgent,
            $statusCode,
            $duration,
            $this->getStatusText($statusCode)
        );

        // Записываем в лог
        $this->writeToLog($logMessage);

        return $response;
    }//end handle()

    private function writeToLog(string $message): void
    {
        // Простая запись в файл
        // В реальном приложении лучше использовать PSR-3 логгер
        file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
    }//end writeToLog()

    private function getStatusText(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            default => 'Unknown'
        };
    }//end getStatusText()
}//end class

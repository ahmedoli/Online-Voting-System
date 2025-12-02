<?php

class ErrorHandler
{
    private static $logFile = __DIR__ . '/../logs/app.log';

    public static function init()
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);

        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    public static function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $error = [
            'type' => 'PHP Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        self::logError($error);

        if (self::isProduction()) {
            return true;
        }

        return false;
    }

    public static function handleException($exception)
    {
        $error = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        self::logError($error);

        if (self::isProduction()) {
            http_response_code(500);
            include __DIR__ . '/../error_pages/500.html';
        } else {
            echo "<h1>Uncaught Exception</h1>";
            echo "<pre>" . $exception->__toString() . "</pre>";
        }

        exit;
    }

    private static function logError($error)
    {
        $logEntry = "[{$error['timestamp']}] {$error['type']}: {$error['message']} " .
            "in {$error['file']} on line {$error['line']} " .
            "(IP: {$error['ip']})" . PHP_EOL;

        error_log($logEntry, 3, self::$logFile);
    }

    private static function isProduction()
    {
        return !in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', 'dev.local']);
    }

    public static function logSecurity($event, $details = [])
    {
        $securityLog = [
            'event' => $event,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id()
        ];

        $logEntry = "[SECURITY] [{$securityLog['timestamp']}] {$event}: " .
            json_encode($details) . " (IP: {$securityLog['ip']})" . PHP_EOL;

        error_log($logEntry, 3, self::$logFile);
    }
}

ErrorHandler::init();

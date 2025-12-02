<?php

<<<<<<< HEAD
/**
 * Centralized Error Handler
 * Provides consistent error handling across the application
 */

=======
>>>>>>> b5ab8834287dbd82661f740a10eaaee56c363f3b
class ErrorHandler
{
    private static $logFile = __DIR__ . '/../logs/app.log';

    public static function init()
    {
<<<<<<< HEAD
        // Set up custom error handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);

        // Ensure log directory exists
=======
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);

>>>>>>> b5ab8834287dbd82661f740a10eaaee56c363f3b
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

<<<<<<< HEAD
        // Don't show detailed errors in production
        if (self::isProduction()) {
            return true; // Suppress error output
        }

        return false; // Let PHP handle it normally in development
=======
        if (self::isProduction()) {
            return true;
        }

        return false;
>>>>>>> b5ab8834287dbd82661f740a10eaaee56c363f3b
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

<<<<<<< HEAD
        // Show generic error page in production
=======
>>>>>>> b5ab8834287dbd82661f740a10eaaee56c363f3b
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

<<<<<<< HEAD
// Initialize error handling
=======
>>>>>>> b5ab8834287dbd82661f740a10eaaee56c363f3b
ErrorHandler::init();

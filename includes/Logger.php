<?php

class Logger
{
    private static function logDir(): string
    {
        $dir = ROOT . '/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function logFile(): string
    {
        return self::logDir() . '/app.log';
    }

    public static function write(string $level, string $message, array $context = []): void
    {
        $line = date('Y-m-d H:i:s') . ' [' . strtoupper($level) . '] ' . $message;
        if ($context) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        $line .= PHP_EOL;
        file_put_contents(self::logFile(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function exception(Throwable $e, array $context = []): void
    {
        $context['exception'] = get_class($e);
        $context['file'] = $e->getFile();
        $context['line'] = $e->getLine();
        self::write('error', $e->getMessage(), $context);
        self::write('error', $e->getTraceAsString());
    }

    public static function recent(int $lines = 200): array
    {
        $file = self::logFile();
        if (!is_file($file)) {
            return [];
        }
        $content = file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }
        $all = explode("\n", rtrim($content, "\n"));
        return array_slice($all, -$lines);
    }

    public static function clear(): void
    {
        $file = self::logFile();
        if (is_file($file)) {
            file_put_contents($file, '');
        }
    }

    public static function registerHandlers(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            self::error("PHP error: {$message}", ['file' => $file, 'line' => $line, 'severity' => $severity]);
            return false;
        });

        set_exception_handler(function (Throwable $e): void {
            self::exception($e, ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
            if (self::wantsJsonResponse()) {
                json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            if (self::isAdminRequest()) {
                http_response_code(500);
                echo '<h1>Server Error</h1><p>An error occurred. Details were written to the debug log.</p>';
                echo '<p><a href="/admin/debug.php">Open Debug Log</a></p>';
            }
        });

        register_shutdown_function(function (): void {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::error('Fatal: ' . $err['message'], ['file' => $err['file'], 'line' => $err['line']]);
            }
        });
    }

    public static function isAdminRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/admin/') || str_contains($uri, '/admin');
    }

    public static function isApiRequest(): bool
    {
        return str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    public static function wantsJsonResponse(): bool
    {
        return is_ajax()
            || self::isApiRequest()
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}

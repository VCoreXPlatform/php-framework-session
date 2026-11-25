<?php

declare(strict_types=1);

namespace Core\Http;

use RuntimeException;

final class Session
{
    private const string COOKIE_NAME = 'FRAMEWORKSID';
    private bool $started = false;

    public function __construct(private readonly string $directory, private readonly bool $secure)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!$this->started) {
                throw new RuntimeException('A PHP session was started outside the framework');
            }
            return;
        }

        if (headers_sent() || !is_dir($this->directory) || !is_writable($this->directory)) {
            throw new RuntimeException('Session requires writable storage and unsent response headers');
        }

        if (
            session_name(self::COOKIE_NAME) === false
            || session_module_name('files') === false
            || !session_set_cookie_params($this->cookieOptions() + ['lifetime' => 0])
            || !session_start([
                'save_path' => $this->directory,
                'use_strict_mode' => 1,
                'use_cookies' => 1,
                'use_only_cookies' => 1,
                'use_trans_sid' => 0,
                'cache_limiter' => 'nocache',
            ])
        ) {
            throw new RuntimeException('Unable to start the session');
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function close(): void
    {
        if ($this->started && session_status() === PHP_SESSION_ACTIVE && !session_write_close()) {
            throw new RuntimeException('Unable to save the session');
        }
    }

    public function destroy(): void
    {
        $this->start();

        if (headers_sent()) {
            throw new RuntimeException('Session cookie removal requires unsent response headers');
        }
        if (!session_destroy()) {
            throw new RuntimeException('Unable to destroy the session');
        }
        $_SESSION = [];
        unset($_COOKIE[self::COOKIE_NAME]);
        $this->started = false;

        if (!setcookie(self::COOKIE_NAME, '', $this->cookieOptions() + ['expires' => 1])) {
            throw new RuntimeException('Unable to expire the session cookie');
        }
    }

    private function cookieOptions(): array
    {
        return [
            'path' => '/',
            'domain' => '',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Core\Http;

final class ArraySession implements SessionStore
{
    private array $data = [];
    public private(set) int $rotations = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        $this->rotations++;
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}

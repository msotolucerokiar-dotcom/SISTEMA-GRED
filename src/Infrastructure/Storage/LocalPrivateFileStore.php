<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Support\PrivateFileStore;
use RuntimeException;

final class LocalPrivateFileStore implements PrivateFileStore
{
    public function __construct(private readonly string $directory)
    {
    }

    public function put(string $temporaryPath): string
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
        }
        $key = bin2hex(random_bytes(24));
        $target = $this->path($key);
        if (!move_uploaded_file($temporaryPath, $target)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }
        chmod($target, 0600);
        return $key;
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function path(string $key): string
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $key)) {
            throw new RuntimeException('Clave de archivo inválida.');
        }
        return rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $key;
    }
}

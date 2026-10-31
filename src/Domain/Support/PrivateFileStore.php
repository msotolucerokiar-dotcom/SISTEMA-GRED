<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface PrivateFileStore
{
    public function put(string $temporaryPath): string;

    public function delete(string $key): void;

    public function path(string $key): string;
}

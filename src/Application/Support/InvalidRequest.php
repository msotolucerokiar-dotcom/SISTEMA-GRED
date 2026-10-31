<?php

declare(strict_types=1);

namespace App\Application\Support;

use RuntimeException;

final class InvalidRequest extends RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('La solicitud contiene datos inválidos.');
    }
}

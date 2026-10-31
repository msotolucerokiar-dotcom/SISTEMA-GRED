<?php

declare(strict_types=1);

namespace App\Presentation;

final class DisplayName
{
    public static function withoutDemoMarker(string $name): string
    {
        $clean = preg_replace('/\s*(?:\((?:demo|prueba)\)|\[(?:demo|prueba)\]|de prueba)\s*$/iu', '', $name);
        return trim($clean ?? $name);
    }
}

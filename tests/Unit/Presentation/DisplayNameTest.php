<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation;

use App\Presentation\DisplayName;
use PHPUnit\Framework\TestCase;

final class DisplayNameTest extends TestCase
{
    public function testOnlyRemovesExplicitDemoSuffix(): void
    {
        self::assertSame('Responsable CSI', DisplayName::withoutDemoMarker('Responsable CSI (demo)'));
        self::assertSame('Técnico CSI', DisplayName::withoutDemoMarker('Técnico CSI [Prueba]'));
        self::assertSame('Trabajador', DisplayName::withoutDemoMarker('Trabajador de prueba'));
        self::assertSame('Ana Prueba', DisplayName::withoutDemoMarker('Ana Prueba'));
        self::assertSame('Demóstenes', DisplayName::withoutDemoMarker('Demóstenes'));
    }
}

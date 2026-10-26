<?php

declare(strict_types=1);

namespace App\Tests\Unit\Authorization;

use App\Application\Authorization\CanAccess;
use App\Domain\Authentication\User;
use PHPUnit\Framework\TestCase;

final class CanAccessTest extends TestCase
{
    public function testRequiresMatchingPermission(): void
    {
        $access = new CanAccess();
        $user = new User(1, 'worker@example.test', 'Worker', 'worker', 'Trabajador', ['request.create']);

        self::assertTrue($access->execute($user, 'request.create'));
        self::assertFalse($access->execute($user, 'request.view_all'));
        self::assertFalse($access->execute(null, 'request.create'));
    }
}

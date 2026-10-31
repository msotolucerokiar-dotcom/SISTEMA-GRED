<?php

declare(strict_types=1);

namespace App\Tests\Unit\Authentication;

use App\Application\Authentication\ChangeUserAccess;
use App\Application\Authentication\ListUsers;
use App\Application\Support\InvalidRequest;
use App\Domain\Authentication\UserAdministrationRepository;
use PHPUnit\Framework\TestCase;

final class UserAdministrationTest extends TestCase
{
    public function testListPaginatesUsers(): void
    {
        $users = $this->createMock(UserAdministrationRepository::class);
        $users->method('countUsers')->willReturn(21);
        $users->expects(self::once())->method('listUsers')->with(20, 20)->willReturn([]);
        self::assertSame(2, (new ListUsers($users))->execute(10)['page']);
    }

    public function testSelfChangeIsRejectedBeforePersistence(): void
    {
        $users = $this->createMock(UserAdministrationRepository::class);
        $users->expects(self::never())->method('updateAccess');
        $this->expectException(InvalidRequest::class);
        (new ChangeUserAccess($users))->execute(5, 5, 'worker', '1');
    }

    public function testLastManagerCannotBeRemoved(): void
    {
        $users = $this->createMock(UserAdministrationRepository::class);
        $users->method('updateAccess')->willReturn('last_manager');
        $this->expectException(InvalidRequest::class);
        (new ChangeUserAccess($users))->execute(5, 6, 'worker', '1');
    }

    public function testInvalidRoleAndStateAreRejected(): void
    {
        $users = $this->createMock(UserAdministrationRepository::class);
        $users->expects(self::never())->method('updateAccess');
        $this->expectException(InvalidRequest::class);
        (new ChangeUserAccess($users))->execute(5, 6, 'admin', 'maybe');
    }
}

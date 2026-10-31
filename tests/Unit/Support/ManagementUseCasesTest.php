<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Support\AssignRequest;
use App\Application\Support\ClassifyRequest;
use App\Application\Support\GetRequestForManagement;
use App\Application\Support\InvalidRequest;
use App\Application\Support\ViewAllRequests;
use App\Domain\Support\ManagementRepository;
use PHPUnit\Framework\TestCase;

final class ManagementUseCasesTest extends TestCase
{
    public function testGlobalListClampsPage(): void
    {
        $repo = new FakeManagementRepository();
        $result = (new ViewAllRequests($repo))->execute(99);
        self::assertSame(2, $result['page']);
        self::assertSame(10, $repo->offset);
    }

    public function testDetailReturnsOnlyExistingRequest(): void
    {
        $repo = new FakeManagementRepository();
        self::assertNull((new GetRequestForManagement($repo))->execute('SOL-2026-999999'));
        $repo->exists = true;
        self::assertSame([], (new GetRequestForManagement($repo))->execute('SOL-2026-000001')['attachments']);
    }

    public function testClassificationRejectsUnknownOptions(): void
    {
        $repo = new FakeManagementRepository();
        $this->expectException(InvalidRequest::class);
        (new ClassifyRequest($repo))->execute('SOL-2026-000001', 999, 1, 5);
    }

    public function testClassificationPassesValidatedValues(): void
    {
        $repo = new FakeManagementRepository();
        self::assertTrue((new ClassifyRequest($repo))->execute('SOL-2026-000001', 1, 1, 5));
        self::assertSame(['SOL-2026-000001', 1, 1, 5], $repo->classified);
    }

    public function testAssignmentRejectsInactiveOrNonTechnician(): void
    {
        $repo = new FakeManagementRepository();
        $this->expectException(InvalidRequest::class);
        (new AssignRequest($repo))->execute('SOL-2026-000001', 999, 5);
    }

    public function testAssignmentPassesActiveTechnician(): void
    {
        $repo = new FakeManagementRepository();
        self::assertTrue((new AssignRequest($repo))->execute('SOL-2026-000001', 7, 5));
        self::assertSame(['SOL-2026-000001', 7, 5], $repo->assigned);
    }
}

final class FakeManagementRepository implements ManagementRepository
{
    public int $offset = -1;
    public bool $exists = false;
    public array $classified = [];
    public array $assigned = [];
    public function listAll(int $limit, int $offset): array { $this->offset = $offset; return []; }
    public function countAll(): int { return 11; }
    public function find(string $code): ?array { return $this->exists ? ['public_code' => $code] : null; }
    public function attachments(string $code): array { return []; }
    public function attachment(int $id): ?array { return null; }
    public function categories(): array { return [['id' => 1, 'name' => 'Conectividad']]; }
    public function priorities(): array { return [['id' => 1, 'name' => 'Baja']]; }
    public function technicians(): array { return [['id' => 7, 'name' => 'Técnico']]; }
    public function classify(string $code, int $categoryId, int $priorityId, int $actorId): bool { $this->classified = [$code, $categoryId, $priorityId, $actorId]; return true; }
    public function assign(string $code, int $technicianId, int $actorId): bool { $this->assigned = [$code, $technicianId, $actorId]; return true; }
}

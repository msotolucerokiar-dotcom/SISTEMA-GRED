<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Support\GetOwnRequest;
use App\Application\Support\ListOwnRequests;
use App\Domain\Support\RequestRepository;
use PHPUnit\Framework\TestCase;

final class ReadOwnRequestsTest extends TestCase
{
    public function testListClampsPageAndUsesOwnerId(): void
    {
        $repository = new ReadRepository();
        $result = (new ListOwnRequests($repository))->execute(17, 99);
        self::assertSame(2, $result['page']);
        self::assertSame(10, $repository->lastOffset);
        self::assertSame(17, $repository->lastUserId);
    }

    public function testDetailIsAbsentWhenRepositoryDoesNotFindOwnedRequest(): void
    {
        $repository = new ReadRepository();
        self::assertNull((new GetOwnRequest($repository))->execute(18, 'SOL-2026-000001'));
        self::assertSame(18, $repository->lastUserId);
    }
}

final class ReadRepository implements RequestRepository
{
    public int $lastOffset = -1;
    public int $lastUserId = -1;
    public function create(int $requesterId, int $areaId, string $title, string $description, array $attachments): string { return ''; }
    public function listOwn(int $requesterId, int $limit, int $offset): array { $this->lastUserId = $requesterId; $this->lastOffset = $offset; return []; }
    public function countOwn(int $requesterId): int { return 11; }
    public function countOwnByStatus(int $requesterId): array { return []; }
    public function findOwn(string $code, int $requesterId): ?array { $this->lastUserId = $requesterId; return null; }
    public function attachmentsForOwn(string $code, int $requesterId): array { return []; }
    public function attachmentForOwn(int $attachmentId, int $requesterId): ?array { return null; }
    public function activeAreas(): array { return []; }
}

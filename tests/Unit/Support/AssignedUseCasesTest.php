<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Support\GetAssignedRequest;
use App\Application\Support\InvalidRequest;
use App\Application\Support\ListAssignedRequests;
use App\Application\Support\UpdateAssignedRequest;
use App\Domain\Support\AssignedRequestRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AssignedUseCasesTest extends TestCase
{
    /** @var AssignedRequestRepository&MockObject */
    private AssignedRequestRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(AssignedRequestRepository::class);
    }

    public function testListPaginatesAssignedCases(): void
    {
        $this->repo->method('countAssigned')->with(7)->willReturn(12);
        $this->repo->expects(self::once())->method('listAssigned')->with(7, 10, 10)->willReturn([]);
        self::assertSame(2, (new ListAssignedRequests($this->repo))->execute(7, 99)['page']);
    }

    public function testGetOnlyLoadsAttachmentsForFoundRequest(): void
    {
        $this->repo->method('findAssigned')->with('SOL-2026-000001', 7)->willReturn(null);
        $this->repo->expects(self::never())->method('attachments');
        self::assertNull((new GetAssignedRequest($this->repo))->execute(7, 'SOL-2026-000001'));
    }

    public function testResolutionRequiresSolution(): void
    {
        $this->repo->expects(self::never())->method('updateProgress');
        $this->expectException(InvalidRequest::class);
        (new UpdateAssignedRequest($this->repo))->execute(7, 'SOL-2026-000001', 'resolved', 'Revisado', '  ');
    }

    public function testValidProgressIsSentToRepository(): void
    {
        $this->repo->expects(self::once())->method('updateProgress')
            ->with('SOL-2026-000001', 7, 'in_progress', 'Revisado', null)->willReturn(true);
        self::assertTrue((new UpdateAssignedRequest($this->repo))->execute(7, 'SOL-2026-000001', 'in_progress', ' Revisado ', ''));
    }
}

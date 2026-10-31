<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Support\GetRequestHistory;
use App\Application\Support\InvalidRequest;
use App\Application\Support\ReportRequests;
use App\Application\Support\RequestFilters;
use App\Application\Support\SearchRequests;
use App\Domain\Support\InsightsRepository;
use App\Infrastructure\Reporting\PdfReport;
use PHPUnit\Framework\TestCase;

final class InsightsUseCasesTest extends TestCase
{
    public function testSearchPaginatesAndPassesValidatedFilters(): void
    {
        $repo = $this->createMock(InsightsRepository::class);
        $repo->method('countFiltered')->willReturn(23);
        $repo->expects(self::once())->method('listFiltered')->with(self::callback(static fn (array $filters): bool => $filters['status'] === 2 && $filters['search'] === 'SOL'), 10, 20)->willReturn([]);
        $result = (new SearchRequests($repo, new RequestFilters()))->execute(['status' => '2', 'search' => ' SOL '], 99);
        self::assertSame(3, $result['page']);
        self::assertSame(23, $result['total']);
    }

    public function testRejectsInvalidDateAndLongReportRange(): void
    {
        $filters = new RequestFilters();
        try {
            $filters->fromInput(['from' => '2026-02-30']);
            self::fail('Expected invalid date');
        } catch (InvalidRequest $exception) {
            self::assertArrayHasKey('from', $exception->errors);
        }
        $this->expectException(InvalidRequest::class);
        $filters->fromInput(['from' => '2024-01-01', 'to' => '2026-01-01'], true);
    }

    public function testReportCountsAndHistoryScope(): void
    {
        $repo = $this->createMock(InsightsRepository::class);
        $repo->expects(self::once())->method('historyForWorker')->with('SOL-2026-000001', 7)->willReturn([['action' => 'created']]);
        self::assertCount(1, (new GetRequestHistory($repo))->forWorker('SOL-2026-000001', 7));
        $repo->method('countFiltered')->willReturn(4);
        $repo->method('countByStatus')->willReturn([['code' => 'pending', 'name' => 'Pendiente', 'total' => 4]]);
        $result = (new ReportRequests($repo, new RequestFilters()))->summary(['from' => '2026-01-01', 'to' => '2026-02-01']);
        self::assertSame(4, $result['total']);
        self::assertSame(4, $result['statuses'][0]['total']);
    }

    public function testPdfHasValidHeaderPageAndSummary(): void
    {
        $pdf = (new PdfReport())->render([['public_code' => 'SOL-2026-000001', 'created_at' => '2026-01-02', 'status_name' => 'Pendiente', 'requester_name' => 'Ana', 'title' => 'Impresora']], 1, [['code' => 'pending', 'name' => 'Pendiente', 'total' => 1]], '2026-01-01', '2026-02-01');
        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringContainsString('/Type /Page', $pdf);
        self::assertStringContainsString('SOL-2026-000001', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }
}

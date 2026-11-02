<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface InsightsRepository
{
    /** @return list<array<string,mixed>> */
    public function historyForManager(string $code): array;

    /** @return list<array<string,mixed>> */
    public function historyForWorker(string $code, int $workerId): array;

    /** @return list<array<string,mixed>> */
    public function historyForTechnician(string $code, int $technicianId): array;

    /** @param array<string,mixed> $filters */
    public function countFiltered(array $filters): int;

    /** @param array<string,mixed> $filters @return list<array<string,mixed>> */
    public function listFiltered(array $filters, int $limit, int $offset): array;

    /** @param array<string,mixed> $filters @return list<array{code:string,name:string,total:int}> */
    public function countByStatus(array $filters): array;

    /** @return list<array<string,mixed>> */
    public function attentionNeeded(int $limit): array;

    /** @param array<string,mixed> $filters @return iterable<array<string,mixed>> */
    public function exportFiltered(array $filters): iterable;

    /** @return array{statuses:list<array{id:int,name:string}>,areas:list<array{id:int,name:string}>,categories:list<array{id:int,name:string}>,technicians:list<array{id:int,name:string}>} */
    public function filterOptions(): array;
}

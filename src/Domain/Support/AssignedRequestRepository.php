<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface AssignedRequestRepository
{
    /** @return list<array<string,mixed>> */
    public function listAssigned(int $technicianId, int $limit, int $offset): array;

    public function countAssigned(int $technicianId): int;

    /** @return array<string,mixed>|null */
    public function findAssigned(string $code, int $technicianId): ?array;

    /** @return list<array<string,mixed>> */
    public function attachments(string $code, int $technicianId): array;

    /** @return array<string,mixed>|null */
    public function attachment(int $id, int $technicianId): ?array;

    /** @return array<string,mixed>|null */
    public function solution(string $code, int $requesterId): ?array;

    public function updateProgress(string $code, int $technicianId, string $status, string $observation, ?string $solution): bool;
}

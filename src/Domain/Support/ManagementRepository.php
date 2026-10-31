<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface ManagementRepository
{
    /** @return list<array<string,mixed>> */
    public function listAll(int $limit, int $offset): array;
    public function countAll(): int;
    /** @return array<string,mixed>|null */
    public function find(string $code): ?array;
    /** @return list<array<string,mixed>> */
    public function attachments(string $code): array;
    /** @return array<string,mixed>|null */
    public function attachment(int $id): ?array;
    /** @return list<array{id:int,name:string}> */
    public function categories(): array;
    /** @return list<array{id:int,name:string}> */
    public function priorities(): array;
    /** @return list<array{id:int,name:string}> */
    public function technicians(): array;
    public function classify(string $code, int $categoryId, int $priorityId, int $actorId): bool;
    public function assign(string $code, int $technicianId, int $actorId): bool;
}

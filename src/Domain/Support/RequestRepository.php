<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface RequestRepository
{
    /** @param list<array{key:string,name:string,mime:string,size:int}> $attachments */
    public function create(int $requesterId, int $areaId, string $title, string $description, array $attachments): string;

    /** @return list<array<string,mixed>> */
    public function listOwn(int $requesterId, int $limit, int $offset): array;

    public function countOwn(int $requesterId): int;

    /** @return array<string,int> Counts keyed by status code. */
    public function countOwnByStatus(int $requesterId): array;

    /** @return array<string,mixed>|null */
    public function findOwn(string $code, int $requesterId): ?array;

    /** @return list<array<string,mixed>> */
    public function attachmentsForOwn(string $code, int $requesterId): array;

    /** @return array<string,mixed>|null */
    public function attachmentForOwn(int $attachmentId, int $requesterId): ?array;

    /** @return list<array{id:int,name:string}> */
    public function activeAreas(): array;
}

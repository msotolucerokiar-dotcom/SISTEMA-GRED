<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface TaskRepository
{
    /** @return list<array{id:int,name:string}> */
    public function interns(): array;

    /** @return list<array<string,mixed>> */
    public function listForIntern(int $internId): array;

    /** @return array<string,mixed>|null */
    public function findForIntern(int $taskId, int $internId): ?array;

    /** @return list<array<string,mixed>> */
    public function attachmentsForIntern(int $taskId, int $internId): array;

    /** @return array<string,mixed>|null */
    public function attachmentForIntern(int $attachmentId, int $internId): ?array;

    /** @return list<array<string,mixed>> */
    public function tasksForRequest(string $code): array;

    public function delegate(string $code, int $actorId, int $internId, string $instructions, bool $manager): bool;

    public function submit(int $taskId, int $internId, string $note): bool;

    public function approve(int $taskId, int $actorId, bool $manager): bool;

    public function cancel(int $taskId, int $actorId, bool $manager): bool;
}

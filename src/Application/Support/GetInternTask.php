<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\TaskRepository;

final class GetInternTask
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /** @return array{task:array<string,mixed>,attachments:list<array<string,mixed>>}|null */
    public function execute(int $taskId, int $internId): ?array
    {
        $task = $this->tasks->findForIntern($taskId, $internId);
        return $task === null ? null : ['task' => $task, 'attachments' => $this->tasks->attachmentsForIntern($taskId, $internId)];
    }
}

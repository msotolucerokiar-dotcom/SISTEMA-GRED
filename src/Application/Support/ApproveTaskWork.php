<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\TaskRepository;

final class ApproveTaskWork
{
    public function __construct(private readonly TaskRepository $tasks) {}

    public function execute(int $taskId, int $actorId, bool $manager): bool
    {
        return $taskId > 0 && $actorId > 0 && $this->tasks->approve($taskId, $actorId, $manager);
    }
}

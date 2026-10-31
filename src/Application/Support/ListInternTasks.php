<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\TaskRepository;

final class ListInternTasks
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /** @return list<array<string,mixed>> */
    public function execute(int $internId): array { return $this->tasks->listForIntern($internId); }
}

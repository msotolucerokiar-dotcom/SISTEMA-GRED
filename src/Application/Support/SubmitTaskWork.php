<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\TaskRepository;

final class SubmitTaskWork
{
    public function __construct(private readonly TaskRepository $tasks) {}

    public function execute(int $taskId, int $internId, string $note): bool
    {
        $note = trim($note);
        if ($taskId < 1 || $internId < 1 || mb_strlen($note) < 5 || mb_strlen($note) > 5000) {
            throw new InvalidRequest(['work_note' => 'Describe el trabajo realizado en 5 a 5000 caracteres.']);
        }
        return $this->tasks->submit($taskId, $internId, $note);
    }
}

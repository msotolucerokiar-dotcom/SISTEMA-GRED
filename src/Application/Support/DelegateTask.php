<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\TaskRepository;

final class DelegateTask
{
    public function __construct(private readonly TaskRepository $tasks) {}

    public function execute(string $code, int $actorId, int $internId, string $instructions, bool $manager): bool
    {
        $instructions = trim($instructions);
        $errors = [];
        if ($actorId < 1 || $internId < 1 || !in_array($internId, array_column($this->tasks->interns(), 'id'), true)) {
            $errors['intern'] = 'Selecciona un practicante activo.';
        }
        if (mb_strlen($instructions) < 5 || mb_strlen($instructions) > 2000) {
            $errors['instructions'] = 'Describe la tarea en 5 a 2000 caracteres.';
        }
        if ($errors !== []) { throw new InvalidRequest($errors); }
        return $this->tasks->delegate($code, $actorId, $internId, $instructions, $manager);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\ManagementRepository;

final class ClassifyRequest
{
    public function __construct(private readonly ManagementRepository $requests) {}

    public function execute(string $code, int $categoryId, int $priorityId, int $actorId): bool
    {
        if ($categoryId < 1 || $priorityId < 1 || $actorId < 1) {
            throw new InvalidRequest(['classification' => 'Selecciona una categoría y una prioridad válidas.']);
        }
        if (!in_array($categoryId, array_column($this->requests->categories(), 'id'), true)
            || !in_array($priorityId, array_column($this->requests->priorities(), 'id'), true)) {
            throw new InvalidRequest(['classification' => 'Selecciona una categoría y una prioridad válidas.']);
        }
        return $this->requests->classify($code, $categoryId, $priorityId, $actorId);
    }
}

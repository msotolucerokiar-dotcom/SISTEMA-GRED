<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\ManagementRepository;

final class AssignRequest
{
    public function __construct(private readonly ManagementRepository $requests) {}

    public function execute(string $code, int $technicianId, int $actorId): bool
    {
        if ($technicianId < 1 || $actorId < 1 || !in_array($technicianId, array_column($this->requests->technicians(), 'id'), true)) {
            throw new InvalidRequest(['assignment' => 'Selecciona un responsable CSI o técnico activo.']);
        }
        return $this->requests->assign($code, $technicianId, $actorId);
    }
}

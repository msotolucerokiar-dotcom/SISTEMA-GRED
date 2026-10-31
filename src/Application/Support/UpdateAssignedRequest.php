<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\AssignedRequestRepository;

final class UpdateAssignedRequest
{
    public function __construct(private readonly AssignedRequestRepository $requests) {}

    public function execute(int $technicianId, string $code, string $status, string $observation, string $solution): bool
    {
        $observation = trim($observation);
        $solution = trim($solution);
        $errors = [];
        if (!in_array($status, ['in_progress', 'resolved'], true)) {
            $errors['status'] = 'Selecciona una acción válida.';
        }
        if ($observation === '' || mb_strlen($observation) > 2000) {
            $errors['observation'] = 'Escribe una observación de 1 a 2000 caracteres.';
        }
        if ($status === 'resolved' && ($solution === '' || mb_strlen($solution) > 5000)) {
            $errors['solution'] = 'Escribe una solución de 1 a 5000 caracteres.';
        }
        if ($errors !== []) {
            throw new InvalidRequest($errors);
        }
        return $this->requests->updateProgress($code, $technicianId, $status, $observation, $status === 'resolved' ? $solution : null);
    }
}

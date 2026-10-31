<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Application\Support\InvalidRequest;
use App\Domain\Authentication\UserAdministrationRepository;

final class ChangeUserAccess
{
    public function __construct(private readonly UserAdministrationRepository $users) {}

    public function execute(int $actorId, int $userId, string $roleCode, string $active): bool
    {
        $errors = [];
        if ($actorId < 1 || $userId < 1 || $actorId === $userId) { $errors['user'] = 'No puedes cambiar tu propia cuenta desde esta pantalla.'; }
        if (!in_array($roleCode, ['worker', 'technician', 'csi_manager'], true)) { $errors['role'] = 'Selecciona un rol válido.'; }
        if (!in_array($active, ['0', '1'], true)) { $errors['active'] = 'Selecciona un estado válido.'; }
        if ($errors !== []) { throw new InvalidRequest($errors); }
        $result = $this->users->updateAccess($userId, $roleCode, $active === '1');
        if ($result === 'last_manager') { throw new InvalidRequest(['user' => 'Debe permanecer al menos una cuenta responsable CSI activa.']); }
        return $result === 'updated';
    }
}

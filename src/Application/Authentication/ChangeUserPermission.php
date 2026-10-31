<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Application\Support\InvalidRequest;
use App\Domain\Authentication\UserPermissionRepository;

final class ChangeUserPermission
{
    public const GRANTABLE = [
        'request.assign' => 'Asignar y reasignar',
        'request.classify' => 'Clasificar y priorizar',
        'request.delegate' => 'Delegar a practicantes',
        'report.view' => 'Consultar reportes',
    ];

    public function __construct(private readonly UserPermissionRepository $permissions) {}

    public function execute(int $actorId, int $userId, string $permission, string $enabled): bool
    {
        if ($actorId < 1 || $userId < 1 || $actorId === $userId || !isset(self::GRANTABLE[$permission]) || !in_array($enabled, ['0', '1'], true)) {
            throw new InvalidRequest(['permission' => 'Selecciona un permiso y un estado válidos para otro usuario.']);
        }
        $result = $this->permissions->change($actorId, $userId, $permission, $enabled === '1');
        if ($result === 'not_technician') { throw new InvalidRequest(['permission' => 'Los permisos adicionales solo se configuran para técnicos CSI.']); }
        return $result === 'changed';
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Application\Support\InvalidRequest;
use App\Domain\Authentication\UserAdministrationRepository;

final class CreateUser
{
    public const ROLES = ['worker', 'technician', 'csi_manager', 'intern'];

    public function __construct(private readonly UserAdministrationRepository $users) {}

    public function execute(string $fullName, string $email, string $role, string $active, string $password, string $confirmation): void
    {
        $fullName = trim($fullName);
        $email = mb_strtolower(trim($email));
        $errors = [];
        if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 160) { $errors['full_name'] = 'Ingresa los nombres y apellidos (3 a 160 caracteres).'; }
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Ingresa un correo electrónico válido.'; }
        if (!in_array($role, self::ROLES, true)) { $errors['role'] = 'Selecciona un rol.'; }
        if (!in_array($active, ['0', '1'], true)) { $errors['active'] = 'Selecciona el estado de la cuenta.'; }
        if (strlen($password) < 12 || strlen($password) > 128) { $errors['password'] = 'La contraseña inicial debe tener entre 12 y 128 caracteres.'; }
        if ($password !== $confirmation) { $errors['confirmation'] = 'Las contraseñas no coinciden.'; }
        if ($errors !== []) { throw new InvalidRequest($errors); }

        if (!$this->users->createUser($email, $fullName, password_hash($password, PASSWORD_ARGON2ID), $role, $active === '1')) {
            throw new InvalidRequest(['email' => 'Ya existe una cuenta registrada con este correo.']);
        }
    }
}

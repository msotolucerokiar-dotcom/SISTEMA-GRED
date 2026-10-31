<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Application\Support\InvalidRequest;
use App\Domain\Authentication\UserRepository;

final class ChangeInitialPassword
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(int $userId, string $current, string $new, string $confirmation): void
    {
        $errors = [];
        if ($current === '') { $errors['current'] = 'Ingresa la contraseña inicial.'; }
        if (strlen($new) < 12 || strlen($new) > 128) { $errors['new'] = 'La nueva contraseña debe tener entre 12 y 128 caracteres.'; }
        if ($new === $current) { $errors['new'] = 'Elige una contraseña distinta de la inicial.'; }
        if ($new !== $confirmation) { $errors['confirmation'] = 'Las contraseñas no coinciden.'; }
        if ($errors !== []) { throw new InvalidRequest($errors); }
        if (!$this->users->changeInitialPassword($userId, $current, password_hash($new, PASSWORD_ARGON2ID))) {
            throw new InvalidRequest(['current' => 'La contraseña inicial no es correcta.']);
        }
    }
}

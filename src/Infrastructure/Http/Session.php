<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Authentication\User;

final class Session
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $secure = $this->isSecureRequest();
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name('gred_csi_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function authenticate(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->fullName,
            'role_code' => $user->roleCode,
            'role_name' => $user->roleName,
            'permissions' => $user->permissions,
        ];
        unset($_SESSION['csrf_token']);
    }

    public function user(): ?User
    {
        $data = $_SESSION['user'] ?? null;
        if (!is_array($data)) {
            return null;
        }

        return new User($data['id'], $data['email'], $data['full_name'], $data['role_code'], $data['role_name'], $data['permissions']);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => $parameters['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    private function isSecureRequest(): bool
    {
        if (getenv('SESSION_SECURE') === '1') {
            return true;
        }
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}

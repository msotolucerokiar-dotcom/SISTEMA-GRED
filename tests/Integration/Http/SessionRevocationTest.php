<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class SessionRevocationTest extends TestCase
{
    public function testLogoutInvalidatesSessionAndPrivateRoutesRedirect(): void
    {
        $pdo = ContainerFactory::build()->get(PDO::class);
        $email = 'logout-' . bin2hex(random_bytes(8)) . '@example.test';
        $password = bin2hex(random_bytes(16));
        $insert = $pdo->prepare("INSERT INTO users (role_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = 'worker'), :email, 'Logout Test', :hash) RETURNING id");
        $insert->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_ARGON2ID)]);
        $id = (int) $insert->fetchColumn();
        try {
            [$status, $home, $headers] = $this->request('GET', '/');
            self::assertSame(200, $status);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
            [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $match[1] ?? '', 'email' => $email, 'password' => $password]), $this->cookie($headers));
            self::assertSame(303, $status);
            $cookie = $this->cookie($headers);
            [$status, $dashboard] = $this->request('GET', '/dashboard', '', $cookie);
            self::assertSame(200, $status);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $dashboard, $match);
            self::assertSame(419, $this->request('POST', '/logout', '', $cookie)[0]);
            self::assertSame(200, $this->request('GET', '/dashboard', '', $cookie)[0]);
            self::assertSame(303, $this->request('POST', '/logout', http_build_query(['_csrf' => $match[1] ?? '']), $cookie)[0]);
            self::assertSame(303, $this->request('GET', '/dashboard', '', $cookie)[0]);
            self::assertSame(303, $this->request('GET', '/requests', '', $cookie)[0]);
            self::assertSame(303, $this->request('GET', '/csi/users', '', $cookie)[0]);
            self::assertSame(200, $this->request('GET', '/', '', $cookie)[0]);
        } finally {
            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $delete->execute(['id' => $id]);
            $delete = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
            $delete->execute(['email' => $email]);
        }
    }

    public function testExistingSessionFollowsRoleChangeAndIsRevokedAfterDeactivation(): void
    {
        $pdo = ContainerFactory::build()->get(PDO::class);
        $email = 'revocation-' . bin2hex(random_bytes(8)) . '@example.test';
        $password = bin2hex(random_bytes(16));
        $insert = $pdo->prepare("INSERT INTO users (role_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = 'worker'), :email, 'Session Test', :hash) RETURNING id");
        $insert->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_ARGON2ID)]);
        $id = (int) $insert->fetchColumn();
        try {
            [$status, $home, $headers] = $this->request('GET', '/');
            self::assertSame(200, $status);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
            [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $match[1] ?? '', 'email' => $email, 'password' => $password]), $this->cookie($headers));
            self::assertSame(303, $status);
            $cookie = $this->cookie($headers);
            [$status] = $this->request('GET', '/requests', '', $cookie);
            self::assertSame(200, $status);

            $change = $pdo->prepare("UPDATE users SET role_id = (SELECT id FROM roles WHERE code = 'technician') WHERE id = :id");
            $change->execute(['id' => $id]);
            [$status] = $this->request('GET', '/requests', '', $cookie);
            self::assertSame(403, $status);
            [$status, $list, $headers] = $this->request('GET', '/technician/requests', '', $cookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Solicitudes asignadas', $list);
            self::assertStringContainsString('no-store', implode(' ', $headers));

            $disable = $pdo->prepare('UPDATE users SET is_active = FALSE WHERE id = :id');
            $disable->execute(['id' => $id]);
            [$status] = $this->request('GET', '/dashboard', '', $cookie);
            self::assertSame(303, $status);
            [$status, $home] = $this->request('GET', '/', '', $cookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('name="email"', $home);
            [$status] = $this->request('GET', '/technician/requests', '', $cookie);
            self::assertSame(303, $status);
        } finally {
            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $delete->execute(['id' => $id]);
            $delete = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
            $delete->execute(['email' => $email]);
        }
    }

    /** @return array{int,string,list<string>} */
    private function request(string $method, string $path, string $body = '', string $cookie = ''): array
    {
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        if ($cookie !== '') { $headers[] = 'Cookie: ' . $cookie; }
        $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 15]]);
        $result = file_get_contents('http://web' . $path, false, $context);
        $responseHeaders = $http_response_header ?? [];
        preg_match('/HTTP\/\S+ ([0-9]{3})/', $responseHeaders[0] ?? '', $match);
        return [(int) ($match[1] ?? 0), $result === false ? '' : $result, $responseHeaders];
    }

    /** @param list<string> $headers */
    private function cookie(array $headers): string
    {
        foreach ($headers as $line) {
            if (stripos($line, 'Set-Cookie:') === 0) { return explode(';', trim(substr($line, 11)))[0]; }
        }
        return '';
    }
}

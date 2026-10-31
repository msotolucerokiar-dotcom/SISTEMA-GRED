<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoUserPermissionRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class UserCreationFlowTest extends TestCase
{
    public function testManagerCreatesAccountsAndNewUserChangesInitialPassword(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $permissions = $container->get(PdoUserPermissionRepository::class);
        $adminEmail = 'admin-create-' . bin2hex(random_bytes(8)) . '@example.test';
        $adminPassword = bin2hex(random_bytes(16));
        $adminId = $users->create($adminEmail, 'Admin Test', password_hash($adminPassword, PASSWORD_ARGON2ID), 'csi_manager');
        $ids = [$adminId];
        $emails = [$adminEmail];
        $initialPassword = 'Inicial-' . bin2hex(random_bytes(10));
        try {
            [$manager, $csrf] = $this->login($adminEmail, $adminPassword);
            self::assertSame(200, $this->request('GET', '/csi/users/new', '', $manager)[0]);
            foreach (['worker', 'technician', 'intern', 'csi_manager'] as $role) {
                $email = 'web-create-' . bin2hex(random_bytes(8)) . '@example.test';
                $emails[] = $email;
                self::assertSame(303, $this->request('POST', '/csi/users/new', http_build_query([
                    '_csrf' => $csrf, 'full_name' => 'Nueva Persona', 'email' => strtoupper($email), 'role' => $role,
                    'active' => '1', 'password' => $initialPassword, 'confirmation' => $initialPassword,
                ]), $manager)[0]);
                $record = $users->findForAuthentication($email);
                self::assertNotNull($record);
                $ids[] = $record['id'];
                self::assertSame($role, $record['role_code']);
                self::assertTrue($record['must_change_password']);
                self::assertTrue(password_verify($initialPassword, $record['password_hash']));
                self::assertNotSame($initialPassword, $record['password_hash']);
                if ($role === 'technician') {
                    self::assertSame([], $permissions->grantsFor($record['id']));
                    self::assertNotContains('request.assign', $record['permissions']);
                    self::assertContains('request.view_assigned', $record['permissions']);
                }
                if ($role === 'intern') { self::assertNotContains('user.manage_roles', $record['permissions']); }
                if ($role === 'worker') { self::assertContains('request.view_own', $record['permissions']); }
            }
            $listing = $this->request('GET', '/csi/users?created=1', '', $manager)[1];
            self::assertStringContainsString('El usuario se creó correctamente', $listing);
            self::assertStringContainsString('Nueva Persona', $listing);
            $duplicate = $this->request('POST', '/csi/users/new', http_build_query([
                '_csrf' => $csrf, 'full_name' => 'Otra Persona', 'email' => $emails[1], 'role' => 'worker',
                'active' => '1', 'password' => $initialPassword, 'confirmation' => $initialPassword,
            ]), $manager);
            self::assertSame(422, $duplicate[0]);
            self::assertStringContainsString('Ya existe una cuenta registrada con este correo', $duplicate[1]);
            $inactiveEmail = 'inactive-create-' . bin2hex(random_bytes(8)) . '@example.test';
            $emails[] = $inactiveEmail;
            self::assertSame(303, $this->request('POST', '/csi/users/new', http_build_query([
                '_csrf' => $csrf, 'full_name' => 'Cuenta Inactiva', 'email' => $inactiveEmail, 'role' => 'worker',
                'active' => '0', 'password' => $initialPassword, 'confirmation' => $initialPassword,
            ]), $manager)[0]);
            $inactiveRecord = $users->findForAuthentication($inactiveEmail);
            self::assertNotNull($inactiveRecord);
            $ids[] = $inactiveRecord['id'];
            self::assertFalse($inactiveRecord['is_active']);
            [$status, $home, $headers] = $this->request('GET', '/');
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
            self::assertSame(401, $this->request('POST', '/login', http_build_query([
                '_csrf' => $match[1] ?? '', 'email' => $inactiveEmail, 'password' => $initialPassword,
            ]), $this->cookie($headers))[0]);
            $invalid = $this->request('POST', '/csi/users/new', http_build_query([
                '_csrf' => $csrf, 'full_name' => 'Otra Persona', 'email' => 'wrong', 'role' => 'bogus',
                'active' => '1', 'password' => 'short', 'confirmation' => 'different',
            ]), $manager);
            self::assertSame(422, $invalid[0]);
            self::assertStringContainsString('Otra Persona', $invalid[1]);
            self::assertStringNotContainsString('value="short"', $invalid[1]);
            self::assertSame(419, $this->request('POST', '/csi/users/new', http_build_query(['email' => 'csrf@example.test']), $manager)[0]);

            [$worker, $workerCsrf, $redirect] = $this->login($emails[1], $initialPassword);
            self::assertSame('/change-password', $redirect);
            self::assertSame(303, $this->request('GET', '/dashboard', '', $worker)[0]);
            self::assertSame(303, $this->request('GET', '/csi/users/new', '', $worker)[0]);
            $rejectedPassword = 'Nueva-' . bin2hex(random_bytes(10));
            self::assertSame(422, $this->request('POST', '/change-password', http_build_query([
                '_csrf' => $workerCsrf, 'current_password' => 'incorrecta', 'new_password' => $rejectedPassword, 'confirmation' => $rejectedPassword,
            ]), $worker)[0]);
            $newPassword = 'Nueva-' . bin2hex(random_bytes(10));
            [$status, , $passwordHeaders] = $this->request('POST', '/change-password', http_build_query([
                '_csrf' => $workerCsrf, 'current_password' => $initialPassword, 'new_password' => $newPassword, 'confirmation' => $newPassword,
            ]), $worker);
            self::assertSame(303, $status);
            $worker = $this->cookie($passwordHeaders);
            self::assertSame(200, $this->request('GET', '/dashboard', '', $worker)[0]);
            self::assertSame(403, $this->request('GET', '/csi/users/new', '', $worker)[0]);
            self::assertFalse($users->findForAuthentication($emails[1])['must_change_password']);
            self::assertTrue(password_verify($newPassword, $users->findForAuthentication($emails[1])['password_hash']));
            [$status, $home, $headers] = $this->request('GET', '/');
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
            self::assertSame(401, $this->request('POST', '/login', http_build_query([
                '_csrf' => $match[1] ?? '', 'email' => $emails[1], 'password' => $initialPassword,
            ]), $this->cookie($headers))[0]);
            [, , $redirect] = $this->login($emails[1], $newPassword);
            self::assertSame('/dashboard', $redirect);

            $technicianId = $ids[2];
            self::assertSame('changed', $permissions->change($adminId, $technicianId, 'request.assign', true));
            [$technician, $techCsrf, $redirect] = $this->login($emails[2], $initialPassword);
            self::assertSame('/change-password', $redirect);
            $techPassword = 'Personal-' . bin2hex(random_bytes(10));
            [$status, , $techHeaders] = $this->request('POST', '/change-password', http_build_query([
                '_csrf' => $techCsrf, 'current_password' => $initialPassword, 'new_password' => $techPassword, 'confirmation' => $techPassword,
            ]), $technician);
            self::assertSame(303, $status);
            $technician = $this->cookie($techHeaders);
            self::assertSame(403, $this->request('POST', '/csi/users/new', http_build_query(['_csrf' => $csrf]), $technician)[0]);
        } finally {
            foreach ($ids as $id) { $query = $pdo->prepare('DELETE FROM users WHERE id = :id'); $query->execute(['id' => $id]); }
            foreach ($emails as $email) { $query = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email'); $query->execute(['email' => $email]); }
        }
    }

    /** @return array{string,string,string} */
    private function login(string $email, string $password): array
    {
        [$status, $home, $headers] = $this->request('GET', '/');
        self::assertSame(200, $status);
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
        [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $match[1] ?? '', 'email' => $email, 'password' => $password]), $this->cookie($headers));
        self::assertSame(303, $status);
        $cookie = $this->cookie($headers);
        $redirect = $this->header($headers, 'Location');
        [$status, $page] = $this->request('GET', $redirect, '', $cookie);
        self::assertSame(200, $status);
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $page, $match);
        return [$cookie, $match[1] ?? '', $redirect];
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
    private function cookie(array $headers): string { return explode(';', $this->header($headers, 'Set-Cookie'))[0]; }
    /** @param list<string> $headers */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $line) { if (stripos($line, $name . ':') === 0) { return trim(substr($line, strlen($name) + 1)); } }
        return '';
    }
}

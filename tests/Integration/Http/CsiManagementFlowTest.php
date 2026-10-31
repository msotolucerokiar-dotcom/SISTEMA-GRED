<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CsiManagementFlowTest extends TestCase
{
    public function testManagerCanClassifyAndAssignWhileWorkerCannotAccessCsiRoutes(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $area = $container->get(PdoRequestRepository::class)->activeAreas()[0]['id'];
        $users = [];
        $emails = [];
        $password = bin2hex(random_bytes(16));
        $code = null;
        try {
            foreach (['worker', 'csi_manager', 'technician'] as $role) {
                $email = 'csi-http-' . bin2hex(random_bytes(8)) . '@example.test';
                $statement = $pdo->prepare("INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = :role), :area, :email, :name, :hash) RETURNING id");
                $statement->execute(['role' => $role, 'area' => $area, 'email' => $email, 'name' => 'Http ' . $role, 'hash' => password_hash($password, PASSWORD_ARGON2ID)]);
                $users[] = (int) $statement->fetchColumn();
                $emails[] = $email;
            }
            $code = $container->get(PdoRequestRepository::class)->create($users[0], $area, 'Sin Internet', 'No hay Internet en la oficina principal.', []);
            $workerCookie = $this->login($emails[0], $password);
            [$status] = $this->request('GET', '/csi/requests', '', $workerCookie);
            self::assertSame(403, $status);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', 'technician=' . $users[2], $workerCookie);
            self::assertSame(403, $status);

            $managerCookie = $this->login($emails[1], $password);
            [$status, $list] = $this->request('GET', '/csi/requests', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $list);
            [$status, $detail] = $this->request('GET', '/csi/requests/' . $code, '', $managerCookie);
            self::assertSame(200, $status);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $detail, $match);
            $csrf = $match[1] ?? '';
            self::assertNotSame('', $csrf);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', 'technician=' . $users[2], $managerCookie);
            self::assertSame(419, $status);
            $category = $container->get(PdoManagementRepository::class)->categories()[0]['id'];
            $priority = $container->get(PdoManagementRepository::class)->priorities()[0]['id'];
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/classify', http_build_query(['_csrf' => $csrf, 'category' => $category, 'priority' => $priority]), $managerCookie);
            self::assertSame(303, $status);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $csrf, 'technician' => $users[2]]), $managerCookie);
            self::assertSame(303, $status);
            [$status, $detail] = $this->request('GET', '/csi/requests/' . $code, '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Http technician', $detail);
        } finally {
            if ($code !== null) {
                $statement = $pdo->prepare('DELETE FROM requests WHERE public_code = :code');
                $statement->execute(['code' => $code]);
            }
            foreach ($users as $id) {
                $statement = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $statement->execute(['id' => $id]);
            }
            foreach ($emails as $email) {
                $statement = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
                $statement->execute(['email' => $email]);
            }
        }
    }

    private function login(string $email, string $password): string
    {
        [$status, $home, $headers] = $this->request('GET', '/');
        self::assertSame(200, $status);
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
        $cookie = $this->cookie($headers);
        [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $match[1] ?? '', 'email' => $email, 'password' => $password]), $cookie);
        self::assertSame(303, $status);
        return $this->cookie($headers);
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

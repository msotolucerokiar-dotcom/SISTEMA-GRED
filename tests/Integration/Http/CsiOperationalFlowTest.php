<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoRequestRepository;
use App\Infrastructure\Persistence\PdoTaskRepository;
use App\Infrastructure\Persistence\PdoManagementRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CsiOperationalFlowTest extends TestCase
{
    public function testIndividualPermissionsAndInternWorkFlow(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $area = $container->get(PdoRequestRepository::class)->activeAreas()[0]['id'];
        $ids = [];
        $emails = [];
        $password = bin2hex(random_bytes(16));
        $code = null;
        try {
            foreach (['worker', 'csi_manager', 'technician', 'intern'] as $role) {
                $email = 'csi-operational-' . bin2hex(random_bytes(8)) . '@example.test';
                $insert = $pdo->prepare('INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = :role), :area, :email, :name, :hash) RETURNING id');
                $insert->execute(['role' => $role, 'area' => $area, 'email' => $email, 'name' => 'Flow ' . $role, 'hash' => password_hash($password, PASSWORD_ARGON2ID)]);
                $ids[] = (int) $insert->fetchColumn();
                $emails[] = $email;
            }
            $code = $container->get(PdoRequestRepository::class)->create($ids[0], $area, 'Impresora sin red', 'La impresora no responde en oficina.', []);
            [$manager, $managerCsrf] = $this->login($emails[1], $password);
            [$technician, $techCsrf] = $this->login($emails[2], $password);
            [$intern, $internCsrf] = $this->login($emails[3], $password);
            [$worker] = $this->login($emails[0], $password);

            self::assertSame(403, $this->request('GET', '/csi/requests', '', $technician)[0]);
            self::assertSame(403, $this->request('GET', '/csi/reports', '', $technician)[0]);
            self::assertSame(403, $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $techCsrf, 'technician' => $ids[2]]), $technician)[0]);
            self::assertSame(403, $this->request('POST', '/csi/users/' . $ids[2] . '/permissions', http_build_query(['_csrf' => $techCsrf, 'permission' => 'request.assign', 'enabled' => '1']), $technician)[0]);
            self::assertSame(403, $this->request('GET', '/csi/requests', '', $intern)[0]);
            self::assertSame(200, $this->request('GET', '/intern/tasks', '', $intern)[0]);
            self::assertSame(419, $this->grant($manager, '', $ids[2], 'request.assign', '1'));
            self::assertSame(422, $this->grant($manager, $managerCsrf, $ids[2], 'user.manage_roles', '1'));
            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.assign', '1'));
            self::assertSame(200, $this->request('GET', '/csi/requests', '', $technician)[0]);
            self::assertSame(403, $this->request('POST', '/csi/requests/' . $code . '/classify', http_build_query(['_csrf' => $techCsrf, 'category' => 1, 'priority' => 1]), $technician)[0]);
            self::assertSame(303, $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $techCsrf, 'technician' => $ids[2]]), $technician)[0]);
            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.assign', '0'));
            self::assertSame(403, $this->request('GET', '/csi/requests', '', $technician)[0]);
            self::assertSame(200, $this->request('GET', '/technician/requests/' . $code, '', $technician)[0]);

            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.classify', '1'));
            $management = $container->get(PdoManagementRepository::class);
            self::assertSame(303, $this->request('POST', '/csi/requests/' . $code . '/classify', http_build_query([
                '_csrf' => $techCsrf, 'category' => $management->categories()[0]['id'], 'priority' => $management->priorities()[0]['id'],
            ]), $technician)[0]);
            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.classify', '0'));
            self::assertSame(403, $this->request('GET', '/csi/requests', '', $technician)[0]);

            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'report.view', '1'));
            self::assertSame(200, $this->request('GET', '/csi/reports', '', $technician)[0]);
            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'report.view', '0'));
            self::assertSame(403, $this->request('GET', '/csi/reports', '', $technician)[0]);

            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.delegate', '1'));
            self::assertSame(303, $this->request('POST', '/technician/requests/' . $code . '/delegate', http_build_query(['_csrf' => $techCsrf, 'intern' => $ids[3], 'instructions' => 'Revisar cable y conexión de la impresora.']), $technician)[0]);
            $taskId = (int) $container->get(PdoTaskRepository::class)->listForIntern($ids[3])[0]['id'];
            self::assertSame(403, $this->request('GET', '/intern/tasks/' . $taskId, '', $worker)[0]);
            self::assertSame(200, $this->request('GET', '/intern/tasks/' . $taskId, '', $intern)[0]);
            self::assertSame(419, $this->request('POST', '/intern/tasks/' . $taskId . '/submit', 'work_note=Revisado', $intern)[0]);
            self::assertSame(303, $this->request('POST', '/intern/tasks/' . $taskId . '/submit', http_build_query(['_csrf' => $internCsrf, 'work_note' => 'Se reconectó el cable de red.']), $intern)[0]);
            self::assertSame(403, $this->request('POST', '/technician/tasks/' . $taskId . '/approve', http_build_query(['_csrf' => $internCsrf]), $intern)[0]);
            self::assertSame(303, $this->grant($manager, $managerCsrf, $ids[2], 'request.delegate', '0'));
            self::assertSame(403, $this->request('POST', '/technician/requests/' . $code . '/delegate', http_build_query(['_csrf' => $techCsrf, 'intern' => $ids[3], 'instructions' => 'Otra tarea.']), $technician)[0]);
            self::assertSame(303, $this->request('POST', '/technician/tasks/' . $taskId . '/approve', http_build_query(['_csrf' => $techCsrf]), $technician)[0]);
            self::assertSame(303, $this->request('POST', '/csi/requests/' . $code . '/delegate', http_build_query(['_csrf' => $managerCsrf, 'intern' => $ids[3], 'instructions' => 'Comprobar otra vez la conectividad.']), $manager)[0]);
            $cancelId = (int) $container->get(PdoTaskRepository::class)->listForIntern($ids[3])[0]['id'];
            self::assertSame(303, $this->request('POST', '/technician/tasks/' . $cancelId . '/cancel', http_build_query(['_csrf' => $techCsrf]), $technician)[0]);
            self::assertSame(303, $this->request('POST', '/technician/requests/' . $code . '/progress', http_build_query(['_csrf' => $techCsrf, 'status' => 'in_progress', 'observation' => 'Atención iniciada.']), $technician)[0]);
            self::assertSame(303, $this->request('POST', '/technician/requests/' . $code . '/progress', http_build_query(['_csrf' => $techCsrf, 'status' => 'resolved', 'observation' => 'Trabajo validado.', 'solution' => 'Impresora reconectada.']), $technician)[0]);
            self::assertStringContainsString('Impresora reconectada.', $this->request('GET', '/requests/' . $code, '', $worker)[1]);
            $history = $pdo->prepare('SELECT action FROM user_permission_history WHERE user_id = :user ORDER BY id');
            $history->execute(['user' => $ids[2]]);
            self::assertSame(['granted', 'revoked', 'granted', 'revoked', 'granted', 'revoked', 'granted', 'revoked'], $history->fetchAll(PDO::FETCH_COLUMN));
        } finally {
            if ($code !== null) { $delete = $pdo->prepare('DELETE FROM requests WHERE public_code = :code'); $delete->execute(['code' => $code]); }
            foreach ($ids as $id) { $delete = $pdo->prepare('DELETE FROM users WHERE id = :id'); $delete->execute(['id' => $id]); }
            foreach ($emails as $email) { $delete = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email'); $delete->execute(['email' => $email]); }
        }
    }

    private function grant(string $cookie, string $csrf, int $userId, string $permission, string $enabled): int
    {
        return $this->request('POST', '/csi/users/' . $userId . '/permissions', http_build_query(['_csrf' => $csrf, 'permission' => $permission, 'enabled' => $enabled]), $cookie)[0];
    }

    /** @return array{string,string} */
    private function login(string $email, string $password): array
    {
        [$status, $home, $headers] = $this->request('GET', '/');
        self::assertSame(200, $status);
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $home, $match);
        [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $match[1] ?? '', 'email' => $email, 'password' => $password]), $this->cookie($headers));
        self::assertSame(303, $status);
        $cookie = $this->cookie($headers);
        [$status, $dashboard] = $this->request('GET', '/dashboard', '', $cookie);
        self::assertSame(200, $status);
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $dashboard, $match);
        return [$cookie, $match[1] ?? ''];
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
        foreach ($headers as $line) { if (stripos($line, 'Set-Cookie:') === 0) { return explode(';', trim(substr($line, 11)))[0]; } }
        return '';
    }
}

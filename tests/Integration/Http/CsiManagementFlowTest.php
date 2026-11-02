<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoInsightsRepository;
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
            $code = $container->get(PdoRequestRepository::class)->create($users[0], $area, '=SUM(1,2) Sin Internet', 'No hay Internet en la oficina principal.', []);
            $workerCookie = $this->login($emails[0], $password);
            [$status] = $this->request('GET', '/csi/requests', '', $workerCookie);
            self::assertSame(403, $status);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', 'technician=' . $users[2], $workerCookie);
            self::assertSame(403, $status);

            $managerCookie = $this->login($emails[1], $password);
            [$status, $managerDashboard] = $this->request('GET', '/dashboard', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Estado de las solicitudes', $managerDashboard);
            self::assertStringContainsString('De la solicitud al resultado', $managerDashboard);
            self::assertStringContainsString('aria-label="Recorrido habitual del Responsable CSI"', $managerDashboard);
            self::assertStringContainsString('href="#manager-overview"', $managerDashboard);
            self::assertStringContainsString('href="#manager-attention-actions"', $managerDashboard);
            self::assertStringContainsString('href="#manager-follow-up"', $managerDashboard);
            self::assertStringContainsString('Carga de trabajo', $managerDashboard);
            self::assertStringContainsString('Http technician', $managerDashboard);
            self::assertStringContainsString('assignment=unassigned', $managerDashboard);
            self::assertStringContainsString('status=', $managerDashboard);
            self::assertStringContainsString('Solicitudes que necesitan atención', $managerDashboard);
            self::assertStringContainsString('/csi/requests?attention=needed', $managerDashboard);
            self::assertStringContainsString($code, $managerDashboard);
            self::assertStringContainsString('/csi/requests/' . $code . '/assign', $managerDashboard);
            self::assertStringContainsString('/csi/requests/' . $code . '/classify', $managerDashboard);
            self::assertStringContainsString('href="/dashboard?period=today"', $managerDashboard);
            self::assertStringContainsString('href="/dashboard?period=month"', $managerDashboard);
            self::assertSame(200, $this->request('GET', '/dashboard?period=today', '', $managerCookie)[0]);
            [$status, $unassigned] = $this->request('GET', '/csi/requests?assignment=unassigned', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $unassigned);
            self::assertStringContainsString('value="unassigned" selected', $unassigned);
            self::assertSame(422, $this->request('GET', '/csi/requests?assignment=invalid', '', $managerCookie)[0]);
            [$status, $attentionList] = $this->request('GET', '/csi/requests?attention=needed', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $attentionList);
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
            [$status, , $actionHeaders] = $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $csrf, 'technician' => $users[2], 'return_to' => 'dashboard']), $managerCookie);
            self::assertSame(303, $status);
            self::assertStringContainsString('/dashboard?saved=assign', implode(' ', $actionHeaders));
            self::assertStringContainsString('La lista de intervención se actualizó', $this->request('GET', '/dashboard?saved=assign', '', $managerCookie)[1]);
            [$status, $detail] = $this->request('GET', '/csi/requests/' . $code, '', $managerCookie);
            self::assertSame(200, $status);
            [$status, $unassigned] = $this->request('GET', '/csi/requests?assignment=unassigned', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringNotContainsString($code, $unassigned);
            self::assertStringContainsString('Http technician', $detail);
            self::assertStringContainsString('Historial de acciones', $detail);
            self::assertStringContainsString('Técnico asignado', $detail);
            [$status, $attentionList] = $this->request('GET', '/csi/requests?attention=needed', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringNotContainsString($code, $attentionList);
            [$status, $workloadDashboard] = $this->request('GET', '/dashboard', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('/csi/requests?technician=' . $users[2], $workloadDashboard);
            self::assertStringContainsString('/csi/requests/' . $code . '#asignar-responsable', $workloadDashboard);
            self::assertStringContainsString('id="asignar-responsable"', $detail);
            [$status, $technicianCases] = $this->request('GET', '/csi/requests?technician=' . $users[2], '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $technicianCases);
            self::assertSame(303, $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $csrf, 'technician' => $users[1]]), $managerCookie)[0]);
            $reassigned = $container->get(PdoInsightsRepository::class)->managerFollowUp(50)['reassigned'];
            self::assertContains($code, array_column($reassigned, 'public_code'));
            $reassignment = $reassigned[array_search($code, array_column($reassigned, 'public_code'), true)];
            self::assertSame('Http technician', $reassignment['previous_name']);
            self::assertSame('Http csi_manager', $reassignment['next_name']);
            self::assertStringContainsString('Cambios de responsable', $this->request('GET', '/dashboard', '', $managerCookie)[1]);
            self::assertStringNotContainsString($code, $this->request('GET', '/csi/requests?technician=' . $users[2], '', $managerCookie)[1]);
            self::assertStringContainsString($code, $this->request('GET', '/csi/requests?technician=' . $users[1], '', $managerCookie)[1]);
            [$status, $workerDetail] = $this->request('GET', '/requests/' . $code, '', $workerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Historial de acciones', $workerDetail);
            [$status, $filtered] = $this->request('GET', '/csi/requests?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $filtered);
            [$status] = $this->request('GET', '/csi/requests?from=2026-02-30', '', $managerCookie);
            self::assertSame(422, $status);
            [$status] = $this->request('GET', '/csi/reports', '', $workerCookie);
            self::assertSame(403, $status);
            [$status, $report] = $this->request('GET', '/csi/reports?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Reportes de soporte', $report);
            [$status, $csv, $csvHeaders] = $this->request('GET', '/csi/reports.csv?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $csv);
            self::assertStringContainsString("'=SUM(1,2)", $csv);
            self::assertStringContainsString('text/csv', implode(' ', $csvHeaders));
            [$status, $pdf, $pdfHeaders] = $this->request('GET', '/csi/reports.pdf?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringStartsWith('%PDF-1.4', $pdf);
            self::assertStringContainsString($code, $pdf);
            self::assertStringContainsString('application/pdf', implode(' ', $pdfHeaders));
            [$status] = $this->request('GET', '/csi/users', '', $workerCookie);
            self::assertSame(403, $status);
            [$status, $accounts] = $this->request('GET', '/csi/users', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($emails[0], $accounts);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $accounts, $userCsrfMatch);
            $userCsrf = $userCsrfMatch[1] ?? '';
            self::assertNotSame('', $userCsrf);
            [$status] = $this->request('POST', '/csi/users/' . $users[0], 'role=technician&active=1', $managerCookie);
            self::assertSame(419, $status);
            [$status] = $this->request('POST', '/csi/users/' . $users[1], http_build_query(['_csrf' => $userCsrf, 'role' => 'worker', 'active' => '1']), $managerCookie);
            self::assertSame(422, $status);
            [$status] = $this->request('POST', '/csi/users/' . $users[0], http_build_query(['_csrf' => $userCsrf, 'role' => 'technician', 'active' => '1']), $managerCookie);
            self::assertSame(303, $status);
            [$status] = $this->request('GET', '/requests', '', $workerCookie);
            self::assertSame(403, $status);
            [$status] = $this->request('POST', '/csi/users/' . $users[0], http_build_query(['_csrf' => $userCsrf, 'role' => 'worker', 'active' => '1']), $managerCookie);
            self::assertSame(303, $status);
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

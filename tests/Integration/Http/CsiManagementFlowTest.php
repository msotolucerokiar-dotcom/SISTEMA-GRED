<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoInsightsRepository;
use App\Infrastructure\Persistence\PdoAssignedRequestRepository;
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
            self::assertSame(403, $this->request('POST', '/csi/requests/' . $code . '/classify-category', 'category=1', $workerCookie)[0]);
            self::assertSame(403, $this->request('POST', '/csi/requests/' . $code . '/classify-priority', 'priority=1', $workerCookie)[0]);
            self::assertSame(403, $this->request('GET', '/csi/activity', '', $workerCookie)[0]);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', 'technician=' . $users[2], $workerCookie);
            self::assertSame(403, $status);

            $managerCookie = $this->login($emails[1], $password);
            [$status, $managerDashboard] = $this->request('GET', '/dashboard', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('href="/profile">Perfil</a>', $managerDashboard);
            self::assertStringContainsString('aria-label="Menú lateral del Responsable CSI"', $managerDashboard);
            self::assertStringContainsString('Vista general', $managerDashboard);
            self::assertStringContainsString('class="manager-breadcrumb"', $managerDashboard);
            self::assertStringContainsString('class="manager-sidebar__brand"', $managerDashboard);
            self::assertStringContainsString('id="manager-global-search"', $managerDashboard);
            self::assertStringContainsString('class="manager-activity__summary"', $managerDashboard);
            self::assertStringContainsString('action="/logout" method="post"', $managerDashboard);
            self::assertSame(303, $this->request('GET', '/profile')[0]);
            [$status, $profile] = $this->request('GET', '/profile', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($emails[1], $profile);
            self::assertStringContainsString('Http csi_manager', $profile);
            self::assertSame(405, $this->request('POST', '/profile', '', $managerCookie)[0]);
            self::assertStringContainsString('Nuevas por revisar', $managerDashboard);
            self::assertStringContainsString('Decisiones pendientes', $managerDashboard);
            self::assertStringContainsString('href="/assets/app.css?v=', $managerDashboard);
            self::assertStringContainsString('Solicitudes que requieren mi intervenci&oacute;n', $managerDashboard);
            self::assertStringNotContainsString('Ã', $managerDashboard);
            self::assertStringNotContainsString('De la solicitud al resultado', $managerDashboard);
            self::assertStringContainsString('Actividad reciente', $managerDashboard);
            self::assertStringContainsString('Alertas prioritarias', $managerDashboard);
            self::assertStringNotContainsString('Accesos rápidos', $managerDashboard);
            self::assertStringNotContainsString('Indicadores del período', $managerDashboard);
            self::assertStringContainsString('Se registró la solicitud', $managerDashboard);
            self::assertStringContainsString('href="/csi/activity"', $managerDashboard);
            [$status, $activityPage] = $this->request('GET', '/csi/activity', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Historial de actividad', $activityPage);
            self::assertStringContainsString($code, $activityPage);
            self::assertStringContainsString('Carga de trabajo del equipo', $managerDashboard);
            self::assertStringContainsString('Ver equipo', $managerDashboard);
            self::assertStringContainsString('Pendientes', $managerDashboard);
            self::assertStringContainsString('En atenci&oacute;n', $managerDashboard);
            self::assertStringContainsString('Http technician', $managerDashboard);
            self::assertStringContainsString('open=1', $managerDashboard);
            self::assertStringContainsString('status=', $managerDashboard);
            self::assertStringContainsString('Solicitudes que necesitan atenci&oacute;n', $managerDashboard);
            self::assertStringContainsString('/csi/requests?attention=needed', $managerDashboard);
            self::assertStringContainsString($code, $managerDashboard);
            self::assertStringContainsString('/csi/requests/' . $code . '/assign', $managerDashboard);
            self::assertStringContainsString('/csi/requests/' . $code . '/classify', $managerDashboard);
            self::assertStringNotContainsString('id="manager-period"', $managerDashboard);
            self::assertStringContainsString('Nuevas por revisar', $managerDashboard);
            self::assertStringContainsString('Por validar', $managerDashboard);
            [$teamStatus, $teamPage] = $this->request('GET', '/dashboard?section=team', '', $managerCookie);
            self::assertSame(200, $teamStatus);
            self::assertStringContainsString('Distribución del trabajo por integrante', $teamPage);
            self::assertStringContainsString('Con demora', $teamPage);
            self::assertStringContainsString('En espera', $teamPage);
            self::assertStringContainsString('solicitudes en espera de validación de', $teamPage);
            self::assertStringContainsString('Selección parcial', $teamPage);
            self::assertSame(200, $this->request('GET', '/dashboard?section=team&team_selection=all&team_scope=loaded', '', $managerCookie)[0]);
            self::assertStringContainsString('Tareas asignadas', $teamPage);
            self::assertStringContainsString('Validaciones pendientes', $teamPage);
            self::assertSame(200, $this->request('GET', '/dashboard?section=team&tab=tasks', '', $managerCookie)[0]);
            self::assertSame(200, $this->request('GET', '/dashboard?section=team&tab=validation', '', $managerCookie)[0]);
            self::assertStringContainsString('validation=pending', $managerDashboard);
            self::assertSame(200, $this->request('GET', '/dashboard?period=today', '', $managerCookie)[0]);
            [$status, $unassigned] = $this->request('GET', '/csi/requests?assignment=unassigned', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $unassigned);
            self::assertStringContainsString('Asignación: Sin asignar', $unassigned);
            self::assertSame(422, $this->request('GET', '/csi/requests?assignment=invalid', '', $managerCookie)[0]);
            [$status, $attentionList] = $this->request('GET', '/csi/requests?attention=needed', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $attentionList);
            [$status, $list] = $this->request('GET', '/csi/requests', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $list);
            self::assertStringContainsString('urgentes sin asignar', $list);
            self::assertStringContainsString('alert=stale', $list);
            self::assertStringContainsString('alert=missing_priority', $list);
            self::assertStringContainsString('alert=missing_category', $list);
            self::assertStringNotContainsString('Detalle de solicitud prioritaria', $list);
            self::assertStringNotContainsString('id="detalle-solicitud"', $list);
            self::assertStringContainsString('href="/csi/requests/' . $code . '"', $list);
            self::assertStringContainsString('> Registrar solicitud</a>', $list);
            self::assertStringContainsString('id="csi-global-search"', $list);
            self::assertStringContainsString('#asignar-responsable', $list);
            self::assertStringContainsString('#clasificar-solicitud', $list);
            self::assertStringContainsString('#priorizar-solicitud', $list);
            self::assertStringContainsString('Exportar', $list);
            self::assertStringContainsString('Filas por página:', $list);
            self::assertStringContainsString('Última actualización', $list);
            self::assertSame(200, $this->request('GET', '/requests/new', '', $managerCookie)[0]);
            [$status, $searched] = $this->request('GET', '/csi/requests?search=Http%20worker', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $searched);
            [$status, $oldest] = $this->request('GET', '/csi/requests?sort=oldest&search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('sort=oldest', $oldest);
            self::assertStringContainsString($code, $oldest);
            [$status, $sorted] = $this->request('GET', '/csi/requests?sort=updated_desc&per_page=10&search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('<option value="10" selected>', $sorted);
            self::assertStringContainsString($code, $sorted);
            self::assertSame(422, $this->request('GET', '/csi/requests?sort=invalid', '', $managerCookie)[0]);
            self::assertSame(422, $this->request('GET', '/csi/requests?alert=invalid', '', $managerCookie)[0]);
            self::assertSame(200, $this->request('GET', '/csi/requests?alert=stale', '', $managerCookie)[0]);
            [$status, $detail] = $this->request('GET', '/csi/requests/' . $code, '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('id="clasificar-solicitud"', $detail);
            self::assertStringContainsString('id="priorizar-solicitud"', $detail);
            self::assertStringContainsString('/classify-category" method="post"', $detail);
            self::assertStringContainsString('/classify-priority" method="post"', $detail);
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $detail, $match);
            $csrf = $match[1] ?? '';
            self::assertNotSame('', $csrf);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/assign', 'technician=' . $users[2], $managerCookie);
            self::assertSame(419, $status);
            $category = $container->get(PdoManagementRepository::class)->categories()[0]['id'];
            $priority = (int) $pdo->query("SELECT id FROM priorities WHERE code = 'urgent'")->fetchColumn();
            self::assertStringContainsString('data-modal-open="clasificar-' . $code . '"', $managerDashboard);
            self::assertStringContainsString('data-modal-open="priorizar-' . $code . '"', $managerDashboard);
            [$status, , $actionHeaders] = $this->request('POST', '/csi/requests/' . $code . '/classify-category', http_build_query(['_csrf' => $csrf, 'category' => $category, 'return_to' => 'dashboard']), $managerCookie);
            self::assertSame(303, $status);
            self::assertStringContainsString('/dashboard?saved=classify-category', implode(' ', $actionHeaders));
            self::assertSame($category, (int) $container->get(PdoManagementRepository::class)->find($code)['category_id']);
            self::assertNull($container->get(PdoManagementRepository::class)->find($code)['priority_id']);
            [$status] = $this->request('POST', '/csi/requests/' . $code . '/classify-priority', http_build_query(['_csrf' => $csrf, 'priority' => $priority]), $managerCookie);
            self::assertSame(303, $status);
            self::assertSame($priority, (int) $container->get(PdoManagementRepository::class)->find($code)['priority_id']);
            [$status, $urgentList] = $this->request('GET', '/csi/requests?priority=' . $priority . '&open=1', '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $urgentList);
            self::assertStringContainsString('value="' . $priority . '" selected', $urgentList);
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
            self::assertStringContainsString('Estado de atención', $detail);
            self::assertStringContainsString('Asignada · Http technician', $detail);
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
            self::assertStringContainsString('Solicitud reasignada', $this->request('GET', '/dashboard', '', $managerCookie)[1]);
            self::assertStringNotContainsString($code, $this->request('GET', '/csi/requests?technician=' . $users[2], '', $managerCookie)[1]);
            self::assertStringContainsString($code, $this->request('GET', '/csi/requests?technician=' . $users[1], '', $managerCookie)[1]);
            [$status, $workerDetail] = $this->request('GET', '/requests/' . $code, '', $workerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Historial de acciones', $workerDetail);
            [$status, $filtered] = $this->request('GET', '/csi/requests?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $filtered);
            $pdo->prepare("UPDATE requests SET updated_at = NOW() - INTERVAL '3 days' WHERE public_code = :code")->execute(['code' => $code]);
            $pdo->prepare("UPDATE request_assignments SET assigned_at = NOW() - INTERVAL '3 days' WHERE request_id = (SELECT id FROM requests WHERE public_code = :code) AND ended_at IS NULL")->execute(['code' => $code]);
            $pdo->prepare("UPDATE request_history SET created_at = NOW() - INTERVAL '3 days' WHERE request_id = (SELECT id FROM requests WHERE public_code = :code)")->execute(['code' => $code]);
            self::assertStringContainsString($code, $this->request('GET', '/csi/requests?alert=stale', '', $managerCookie)[1]);
            [$status] = $this->request('GET', '/csi/requests?from=2026-02-30', '', $managerCookie);
            self::assertSame(422, $status);
            [$status] = $this->request('GET', '/csi/reports', '', $workerCookie);
            self::assertSame(403, $status);
            [$status, $report] = $this->request('GET', '/csi/reports?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('<h1>Reportes</h1>', $report);
            self::assertStringContainsString('id="report-period"', $report);
            self::assertStringContainsString('Rango personalizado', $report);
            self::assertStringContainsString('Exportar reporte', $report);
            self::assertStringContainsString('class="manager-sidebar"', $report);
            self::assertStringContainsString('href="/csi/reports" aria-current="page"', $report);
            self::assertStringContainsString('Evolución de solicitudes', $report);
            self::assertStringContainsString($code, $this->request('GET', '/csi/reports?tab=attention&search=' . rawurlencode($code), '', $managerCookie)[1]);
            self::assertStringContainsString('Solicitudes por oficina', $this->request('GET', '/csi/reports?tab=office&search=' . rawurlencode($code), '', $managerCookie)[1]);
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
            $progress = $container->get(PdoAssignedRequestRepository::class);
            self::assertTrue($progress->updateProgress($code, $users[1], 'in_progress', 'Atención iniciada.', null));
            self::assertStringNotContainsString($code, $this->request('GET', '/csi/requests?alert=stale', '', $managerCookie)[1]);
            self::assertTrue($progress->updateProgress($code, $users[1], 'resolved', 'Atención completada.', 'Servicio restablecido.'));
            [$reportStatus, $resolvedReport] = $this->request('GET', '/csi/reports?search=' . rawurlencode($code), '', $managerCookie);
            self::assertSame(200, $reportStatus);
            self::assertStringContainsString('Tiempo promedio de resolución', $resolvedReport);
            self::assertStringContainsString('De 1 solicitud con solución registrada', $resolvedReport);
            self::assertStringNotContainsString($code, $this->request('GET', '/csi/requests?priority=' . $priority . '&open=1', '', $managerCookie)[1]);
            [$status, $finishedDetail] = $this->request('GET', '/csi/requests/' . $code, '', $managerCookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('La atención terminó', $finishedDetail);
            self::assertStringNotContainsString('/classify" method="post"', $finishedDetail);
            self::assertStringNotContainsString('/assign" method="post"', $finishedDetail);
            [$status, $terminalError] = $this->request('POST', '/csi/requests/' . $code . '/assign', http_build_query(['_csrf' => $csrf, 'technician' => $users[2]]), $managerCookie);
            self::assertSame(409, $status);
            self::assertStringContainsString('No se puede asignar, reasignar ni clasificar', $terminalError);
            self::assertSame(409, $this->request('POST', '/csi/requests/' . $code . '/classify', http_build_query(['_csrf' => $csrf, 'category' => $category, 'priority' => $priority]), $managerCookie)[0]);
            self::assertStringContainsString('Asignación: Asignada', $this->request('GET', '/requests/' . $code, '', $workerCookie)[1]);
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

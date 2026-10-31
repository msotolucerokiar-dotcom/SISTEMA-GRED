<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\MigrationRunner;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class WorkerRequestFlowTest extends TestCase
{
    public function testWorkerCanCreateViewAndDownloadOwnEvidence(): void
    {
        $container = ContainerFactory::build();
        $container->get(MigrationRunner::class)->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
        $pdo = $container->get(PDO::class);
        $areaId = $container->get(PdoRequestRepository::class)->activeAreas()[0]['id'];
        $email = 'http-' . bin2hex(random_bytes(8)) . '@example.test';
        $password = bin2hex(random_bytes(16));
        $insert = $pdo->prepare("INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = 'worker'), :area, :email, 'Http Worker', :hash) RETURNING id");
        $insert->execute(['area' => $areaId, 'email' => $email, 'hash' => password_hash($password, PASSWORD_ARGON2ID)]);
        $userId = (int) $insert->fetchColumn();
        $code = null;
        try {
            [$status, $home, $headers] = $this->request('GET', '/');
            self::assertSame(200, $status);
            $cookie = $this->cookie($headers);
            $token = $this->csrf($home);
            [$status, , $headers] = $this->request('POST', '/login', http_build_query(['_csrf' => $token, 'email' => $email, 'password' => $password]), 'application/x-www-form-urlencoded', $cookie);
            self::assertSame(303, $status);
            $cookie = $this->cookie($headers);
            [$status, $form] = $this->request('GET', '/requests/new', '', '', $cookie);
            self::assertSame(200, $status);
            $token = $this->csrf($form);

            $boundary = 'GredBoundary' . bin2hex(random_bytes(8));
            $fields = ['_csrf' => $token, 'title' => 'Falla de red en oficina', 'description' => 'No hay acceso a Internet desde el equipo principal.', 'area' => (string) $areaId];
            $body = '';
            foreach ($fields as $name => $value) {
                $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
            }
            $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"evidence[]\"; filename=\"red.pdf\"\r\nContent-Type: application/pdf\r\n\r\n%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\r\n--{$boundary}--\r\n";
            [$status, , $headers] = $this->request('POST', '/requests/new', $body, 'multipart/form-data; boundary=' . $boundary, $cookie);
            self::assertSame(303, $status);
            $location = $this->header($headers, 'Location');
            self::assertMatchesRegularExpression('~^/requests/SOL-[0-9]{4}-[0-9]+$~', $location);
            $code = basename($location);
            [$status, $detail] = $this->request('GET', $location, '', '', $cookie);
            self::assertSame(200, $status);
            self::assertStringContainsString('Falla de red en oficina', $detail);
            self::assertMatchesRegularExpression('~/attachments/[0-9]+~', $detail);
            preg_match('~/attachments/([0-9]+)~', $detail, $match);
            [$status, $contents] = $this->request('GET', '/attachments/' . $match[1], '', '', $cookie);
            self::assertSame(200, $status);
            self::assertStringStartsWith('%PDF-1.4', $contents);
            [$status, $list] = $this->request('GET', '/requests', '', '', $cookie);
            self::assertSame(200, $status);
            self::assertStringContainsString($code, $list);
            [$status] = $this->request('GET', $location);
            self::assertSame(303, $status);
            [$status] = $this->request('GET', '/attachments/' . $match[1]);
            self::assertSame(404, $status);
        } finally {
            $requestId = null;
            if ($code !== null) {
                $find = $pdo->prepare('SELECT id FROM requests WHERE public_code = :code');
                $find->execute(['code' => $code]);
                $requestId = $find->fetchColumn();
                if ($requestId !== false) {
                    $files = $pdo->prepare('SELECT storage_key FROM request_attachments WHERE request_id = :id');
                    $files->execute(['id' => $requestId]);
                    foreach ($files->fetchAll(PDO::FETCH_COLUMN) as $key) {
                        @unlink(dirname(__DIR__, 3) . '/storage/private/' . $key);
                    }
                    $delete = $pdo->prepare('DELETE FROM requests WHERE id = :id');
                    $delete->execute(['id' => $requestId]);
                }
            }
            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $delete->execute(['id' => $userId]);
            $delete = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
            $delete->execute(['email' => $email]);
        }
    }

    /** @return array{int,string,list<string>} */
    private function request(string $method, string $path, string $body = '', string $contentType = '', string $cookie = ''): array
    {
        $headers = [];
        if ($contentType !== '') { $headers[] = 'Content-Type: ' . $contentType; }
        if ($cookie !== '') { $headers[] = 'Cookie: ' . $cookie; }
        $context = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body,
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 15,
        ]]);
        $result = file_get_contents('http://web' . $path, false, $context);
        $responseHeaders = $http_response_header ?? [];
        preg_match('/HTTP\/\S+ ([0-9]{3})/', $responseHeaders[0] ?? '', $match);
        return [(int) ($match[1] ?? 0), $result === false ? '' : $result, $responseHeaders];
    }

    /** @param list<string> $headers */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $line) {
            if (stripos($line, $name . ':') === 0) { return trim(substr($line, strlen($name) + 1)); }
        }
        return '';
    }

    /** @param list<string> $headers */
    private function cookie(array $headers): string
    {
        return explode(';', $this->header($headers, 'Set-Cookie'))[0];
    }

    private function csrf(string $html): string
    {
        preg_match('/name="_csrf" value="([a-f0-9]+)"/', $html, $match);
        return $match[1] ?? '';
    }
}

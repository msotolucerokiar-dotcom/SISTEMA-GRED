<?php

declare(strict_types=1);

use App\Application\Authentication\Login;
use App\Application\Authentication\Logout;
use App\Application\Authorization\CanAccess;
use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Http\Session;
use App\Application\Support\CreateRequest;
use App\Application\Support\GetOwnRequest;
use App\Application\Support\InvalidRequest;
use App\Application\Support\ListOwnRequests;
use App\Domain\Support\PrivateFileStore;
use App\Domain\Support\RequestRepository;
use App\Application\Support\AssignRequest;
use App\Application\Support\ClassifyRequest;
use App\Application\Support\GetRequestForManagement;
use App\Application\Support\ViewAllRequests;
use App\Domain\Support\ManagementRepository;
use App\Domain\Support\AssignedRequestRepository;
use App\Application\Support\ListAssignedRequests;
use App\Application\Support\GetAssignedRequest;
use App\Application\Support\UpdateAssignedRequest;
use App\Application\Support\GetRequestHistory;
use App\Application\Support\SearchRequests;
use App\Application\Support\ReportRequests;
use App\Domain\Support\InsightsRepository;
use App\Infrastructure\Reporting\PdfReport;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = ContainerFactory::build();
$session = new Session();
$login = $container->get(Login::class);
$canAccess = $container->get(CanAccess::class);
$logout = new Logout();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$user = $session->user();
$error = null;
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

if ($path === '/login' && $method === 'POST') {
    if (!$session->verifyCsrf($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
    } else {
        $result = $login->execute(
            is_string($_POST['email'] ?? null) ? $_POST['email'] : '',
            is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        );
        if ($result->succeeded() && $result->user !== null) {
            $session->authenticate($result->user);
            header('Location: /dashboard', true, 303);
            exit;
        }
        http_response_code(401);
        $error = $result->rateLimited
            ? 'Se alcanzó el límite de intentos. Espera 15 minutos antes de volver a intentarlo.'
            : 'El correo o la contraseña no son correctos.';
    }
    $user = $session->user();
}

if ($path === '/logout') {
    if ($method !== 'POST' || !$session->verifyCsrf($_POST['_csrf'] ?? null)) {
        http_response_code($method === 'POST' ? 419 : 405);
        header('Allow: POST');
        $error = 'No se pudo cerrar la sesión. Recarga la página e inténtalo de nuevo.';
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    } else {
        $logout->execute();
        $session->destroy();
        header('Location: /', true, 303);
        exit;
    }
}

if ($path === '/login' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    $error = 'Usa el formulario de acceso para iniciar sesión.';
    require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
    exit;
}

if ($path === '/login' && $method === 'POST') {
    require dirname(__DIR__) . '/src/Presentation/Views/home.php';
    exit;
}

if ($path === '/dashboard') {
    if ($user === null) {
        header('Location: /#acceso', true, 303);
        exit;
    }
    if (!$canAccess->execute($user, 'request.view_own')
        && !$canAccess->execute($user, 'request.view_assigned')
        && !$canAccess->execute($user, 'request.view_all')) {
        http_response_code(403);
        $error = 'Tu cuenta no tiene permisos para acceder a este panel.';
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    require dirname(__DIR__) . '/src/Presentation/Views/dashboard.php';
    exit;
}

if ($path === '/technician/requests' || preg_match('~^/technician/requests/(SOL-[0-9]{4}-[0-9]+)(?:/progress)?$~D', $path, $technicalMatch)) {
    if ($user === null) {
        header('Location: /#acceso', true, 303);
        exit;
    }
    if (!$canAccess->execute($user, 'request.view_assigned')) {
        http_response_code(403);
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    if ($path === '/technician/requests') {
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
            exit;
        }
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $listing = $container->get(ListAssignedRequests::class)->execute($user->id, $page);
        require dirname(__DIR__) . '/src/Presentation/Views/technician-request-list.php';
        exit;
    }
    $code = $technicalMatch[1];
    $updating = str_ends_with($path, '/progress');
    if (($updating && $method !== 'POST') || (!$updating && $method !== 'GET')) {
        http_response_code(405);
        header('Allow: ' . ($updating ? 'POST' : 'GET'));
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    }
    if ($updating && !$canAccess->execute($user, 'request.update_status')) {
        http_response_code(403);
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    $formErrors = [];
    $form = ['observation' => '', 'solution' => ''];
    if ($updating) {
        $form = [
            'observation' => is_string($_POST['observation'] ?? null) ? $_POST['observation'] : '',
            'solution' => is_string($_POST['solution'] ?? null) ? $_POST['solution'] : '',
        ];
        if (!$session->verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            $formErrors['form'] = 'El formulario venció. Recarga la página e inténtalo de nuevo.';
        } else {
            try {
                $updated = $container->get(UpdateAssignedRequest::class)->execute(
                    $user->id, $code,
                    is_string($_POST['status'] ?? null) ? $_POST['status'] : '',
                    $form['observation'], $form['solution']
                );
                if ($updated) {
                    header('Location: /technician/requests/' . $code . '?saved=1', true, 303);
                    exit;
                }
                http_response_code(409);
                $formErrors['form'] = 'La solicitud cambió de estado o fue reasignada. Revisa la información actual.';
            } catch (InvalidRequest $exception) {
                http_response_code(422);
                $formErrors = $exception->errors;
            }
        }
    }
    $detail = $container->get(GetAssignedRequest::class)->execute($user->id, $code);
    if ($detail === null) {
        http_response_code(404);
        require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
        exit;
    }
    $history = $container->get(GetRequestHistory::class)->forTechnician($code, $user->id);
    require dirname(__DIR__) . '/src/Presentation/Views/technician-request-detail.php';
    exit;
}

if (in_array($path, ['/csi/reports', '/csi/reports.csv', '/csi/reports.pdf'], true)) {
    if ($user === null) { header('Location: /#acceso', true, 303); exit; }
    if (!$canAccess->execute($user, 'report.view')) {
        http_response_code(403);
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    }
    $reports = $container->get(ReportRequests::class);
    $filterErrors = [];
    try {
        $summary = $reports->summary($_GET);
    } catch (InvalidRequest $exception) {
        http_response_code(422);
        $filterErrors = $exception->errors;
        $summary = ['filters' => [], 'total' => 0, 'statuses' => []];
    }
    if ($filterErrors !== []) {
        if ($path !== '/csi/reports') { header('Content-Type: text/plain; charset=UTF-8'); echo implode(' ', $filterErrors); exit; }
    } elseif ($path === '/csi/reports.csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="solicitudes-csi.csv"');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Código', 'Fecha', 'Asunto', 'Solicitante', 'Área', 'Categoría', 'Prioridad', 'Estado', 'Técnico'], ';');
        $safe = static function (string $value): string {
            return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
        };
        foreach ($reports->rows($summary['filters']) as $row) {
            fputcsv($output, array_map($safe, [
                $row['public_code'], $row['created_at'], $row['title'], $row['requester_name'], $row['area_name'],
                $row['category_name'] ?? '', $row['priority_name'] ?? '', $row['status_name'], $row['technician_name'] ?? '',
            ]), ';');
        }
        fclose($output);
        exit;
    } elseif ($path === '/csi/reports.pdf') {
        $pdf = $container->get(PdfReport::class)->render($reports->rows($summary['filters']), $summary['total'], $summary['statuses'], $summary['filters']['from'], $summary['filters']['to']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="solicitudes-csi.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }
    $filterOptions = $container->get(InsightsRepository::class)->filterOptions();
    require dirname(__DIR__) . '/src/Presentation/Views/csi-report.php';
    exit;
}

if (preg_match('~^/technician/attachments/([0-9]+)$~D', $path, $technicalAttachmentMatch)) {
    if ($user === null || $method !== 'GET' || !$canAccess->execute($user, 'request.view_assigned')) {
        http_response_code(404);
        exit;
    }
    $attachment = $container->get(AssignedRequestRepository::class)->attachment((int) $technicalAttachmentMatch[1], $user->id);
    if ($attachment === null) {
        http_response_code(404);
        exit;
    }
    $filePath = $container->get(PrivateFileStore::class)->path($attachment['storage_key']);
    if (!is_file($filePath)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $attachment['mime_type']);
    header('Content-Disposition: attachment; filename="evidencia"; filename*=UTF-8\'\'' . rawurlencode($attachment['original_name']));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

if ($path === '/csi/requests' || preg_match('~^/csi/requests/(SOL-[0-9]{4}-[0-9]+)(?:/(classify|assign))?$~D', $path, $managerMatch)) {
    if ($user === null) {
        header('Location: /#acceso', true, 303);
        exit;
    }
    if (!$canAccess->execute($user, 'request.view_all')) {
        http_response_code(403);
        $error = 'Esta sección está disponible para el Responsable CSI. Inicia sesión con una cuenta de ese rol.';
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    $action = $managerMatch[2] ?? null;
    if ($action !== null && (!$canAccess->execute($user, 'request.manage') || $method !== 'POST')) {
        http_response_code($method === 'POST' ? 403 : 405);
        if ($method !== 'POST') { header('Allow: POST'); }
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    }
    if ($action === null && $method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    }
    if ($path === '/csi/requests') {
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $filterErrors = [];
        try {
            $listing = $container->get(SearchRequests::class)->execute($_GET, $page);
        } catch (InvalidRequest $exception) {
            http_response_code(422);
            $filterErrors = $exception->errors;
            $listing = ['filters' => [], 'items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
        }
        $filterOptions = $container->get(InsightsRepository::class)->filterOptions();
        require dirname(__DIR__) . '/src/Presentation/Views/csi-request-list.php';
        exit;
    }
    $code = $managerMatch[1];
    $formErrors = [];
    if ($action !== null) {
        if (!$session->verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            $formErrors[$action] = 'El formulario venció. Recarga la página e inténtalo de nuevo.';
        } else {
            try {
                $updated = $action === 'classify'
                    ? $container->get(ClassifyRequest::class)->execute(
                        $code,
                        (int) (filter_var($_POST['category'] ?? null, FILTER_VALIDATE_INT) ?: 0),
                        (int) (filter_var($_POST['priority'] ?? null, FILTER_VALIDATE_INT) ?: 0),
                        $user->id
                    )
                    : $container->get(AssignRequest::class)->execute(
                        $code,
                        (int) (filter_var($_POST['technician'] ?? null, FILTER_VALIDATE_INT) ?: 0),
                        $user->id
                    );
                if ($updated) {
                    header('Location: /csi/requests/' . $code . '?saved=' . $action, true, 303);
                    exit;
                }
                http_response_code(404);
                require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
                exit;
            } catch (InvalidRequest $exception) {
                http_response_code(422);
                $formErrors = $exception->errors;
            }
        }
    }
    $detail = $container->get(GetRequestForManagement::class)->execute($code);
    if ($detail === null) {
        http_response_code(404);
        require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
        exit;
    }
    $management = $container->get(ManagementRepository::class);
    $categories = $management->categories();
    $priorities = $management->priorities();
    $technicians = $management->technicians();
    $history = $container->get(GetRequestHistory::class)->forManager($code);
    require dirname(__DIR__) . '/src/Presentation/Views/csi-request-detail.php';
    exit;
}

if (preg_match('~^/csi/attachments/([0-9]+)$~D', $path, $managerAttachmentMatch)) {
    if ($user === null || $method !== 'GET' || !$canAccess->execute($user, 'request.view_all')) {
        http_response_code(404);
        exit;
    }
    $attachment = $container->get(ManagementRepository::class)->attachment((int) $managerAttachmentMatch[1]);
    if ($attachment === null) {
        http_response_code(404);
        exit;
    }
    $filePath = $container->get(PrivateFileStore::class)->path($attachment['storage_key']);
    if (!is_file($filePath)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $attachment['mime_type']);
    header('Content-Disposition: attachment; filename="evidencia"; filename*=UTF-8\'\'' . rawurlencode($attachment['original_name']));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

if ($path === '/requests' || $path === '/requests/new' || preg_match('~^/requests/(SOL-[0-9]{4}-[0-9]+)$~D', $path, $requestMatch)) {
    if ($user === null) {
        header('Location: /#acceso', true, 303);
        exit;
    }
    $permission = $path === '/requests/new' ? 'request.create' : 'request.view_own';
    if (!$canAccess->execute($user, $permission)) {
        http_response_code(403);
        require dirname(__DIR__) . '/src/Presentation/Views/forbidden.php';
        exit;
    }
    if ($path === '/requests/new') {
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
            exit;
        }
        $areas = $container->get(RequestRepository::class)->activeAreas();
        $formErrors = [];
        $form = ['title' => '', 'description' => '', 'area' => ''];
        if ($method === 'POST') {
            $form = [
                'title' => is_string($_POST['title'] ?? null) ? $_POST['title'] : '',
                'description' => is_string($_POST['description'] ?? null) ? $_POST['description'] : '',
                'area' => is_string($_POST['area'] ?? null) ? $_POST['area'] : '',
            ];
            if (!$session->verifyCsrf($_POST['_csrf'] ?? null)) {
                http_response_code(419);
                $formErrors['form'] = 'El formulario venció. Recarga la página e inténtalo de nuevo.';
            } else {
                $uploads = [];
                if (isset($_FILES['evidence']) && is_array($_FILES['evidence']['name'] ?? null)) {
                    foreach ($_FILES['evidence']['name'] as $index => $name) {
                        if (($_FILES['evidence']['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $uploads[] = [
                            'name' => (string) $name,
                            'tmp_name' => (string) ($_FILES['evidence']['tmp_name'][$index] ?? ''),
                            'error' => (int) ($_FILES['evidence']['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                            'size' => (int) ($_FILES['evidence']['size'][$index] ?? 0),
                        ];
                    }
                }
                try {
                    $code = $container->get(CreateRequest::class)->execute(
                        $user->id, (int) $form['area'], $form['title'], $form['description'], $uploads
                    );
                    header('Location: /requests/' . rawurlencode($code), true, 303);
                    exit;
                } catch (InvalidRequest $exception) {
                    http_response_code(422);
                    $formErrors = $exception->errors;
                }
            }
        }
        require dirname(__DIR__) . '/src/Presentation/Views/request-new.php';
        exit;
    }
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        require dirname(__DIR__) . '/src/Presentation/Views/method-not-allowed.php';
        exit;
    }
    if ($path === '/requests') {
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $listing = $container->get(ListOwnRequests::class)->execute($user->id, $page);
        require dirname(__DIR__) . '/src/Presentation/Views/request-list.php';
        exit;
    }
    $detail = $container->get(GetOwnRequest::class)->execute($user->id, $requestMatch[1]);
    if ($detail === null) {
        http_response_code(404);
        require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
        exit;
    }
    $solution = $container->get(AssignedRequestRepository::class)->solution($requestMatch[1], $user->id);
    $history = $container->get(GetRequestHistory::class)->forWorker($requestMatch[1], $user->id);
    require dirname(__DIR__) . '/src/Presentation/Views/request-detail.php';
    exit;
}

if (preg_match('~^/attachments/([0-9]+)$~D', $path, $attachmentMatch)) {
    if ($user === null) {
        http_response_code(404);
        exit;
    }
    if ($method !== 'GET' || !$canAccess->execute($user, 'request.view_own')) {
        http_response_code(404);
        exit;
    }
    $attachment = $container->get(RequestRepository::class)->attachmentForOwn((int) $attachmentMatch[1], $user->id);
    if ($attachment === null) {
        http_response_code(404);
        exit;
    }
    $filePath = $container->get(PrivateFileStore::class)->path($attachment['storage_key']);
    if (!is_file($filePath)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $attachment['mime_type']);
    header('Content-Disposition: attachment; filename="evidencia"; filename*=UTF-8\'\'' . rawurlencode($attachment['original_name']));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

if ($path !== '/' || $method !== 'GET') {
    http_response_code(404);
    require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
    exit;
}

require dirname(__DIR__) . '/src/Presentation/Views/home.php';

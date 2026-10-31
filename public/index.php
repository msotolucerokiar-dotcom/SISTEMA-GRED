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

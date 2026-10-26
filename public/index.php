<?php

declare(strict_types=1);

use App\Application\Authentication\Login;
use App\Application\Authentication\Logout;
use App\Application\Authorization\CanAccess;
use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Http\Session;

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

if ($path !== '/' || $method !== 'GET') {
    http_response_code(404);
    require dirname(__DIR__) . '/src/Presentation/Views/not-found.php';
    exit;
}

require dirname(__DIR__) . '/src/Presentation/Views/home.php';

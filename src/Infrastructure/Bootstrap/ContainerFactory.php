<?php

declare(strict_types=1);

namespace App\Infrastructure\Bootstrap;

use App\Infrastructure\Persistence\MigrationRunner;
use App\Application\Authentication\Login;
use App\Application\Authorization\CanAccess;
use App\Domain\Authentication\UserRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Application\Support\CreateRequest;
use App\Application\Support\GetOwnRequest;
use App\Application\Support\ListOwnRequests;
use App\Domain\Support\PrivateFileStore;
use App\Domain\Support\RequestRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use App\Infrastructure\Storage\LocalPrivateFileStore;
use App\Application\Support\AssignRequest;
use App\Application\Support\ClassifyRequest;
use App\Application\Support\GetRequestForManagement;
use App\Application\Support\ViewAllRequests;
use App\Domain\Support\ManagementRepository;
use App\Infrastructure\Persistence\PdoManagementRepository;
use PDO;
use RuntimeException;

/** The single composition root for application adapters. */
final class ContainerFactory
{
    /** @return SimpleContainer<object> */
    public static function build(): SimpleContainer
    {
        $host = self::env('DB_HOST', 'db');
        $port = self::env('DB_PORT', '5432');
        $name = self::env('DB_NAME', 'gred_support');
        $user = self::env('DB_USER', 'gred_app');
        $password = self::env('DB_PASSWORD', 'change-me-local');
        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$name}",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $pdo->exec("SET TIME ZONE 'UTC'");

        $users = new PdoUserRepository($pdo);
        $requests = new PdoRequestRepository($pdo);
        $management = new PdoManagementRepository($pdo);
        $files = new LocalPrivateFileStore(dirname(__DIR__, 3) . '/storage/private');

        return new SimpleContainer([
            PDO::class => $pdo,
            MigrationRunner::class => new MigrationRunner($pdo),
            UserRepository::class => $users,
            PdoUserRepository::class => $users,
            Login::class => new Login($users),
            CanAccess::class => new CanAccess(),
            RequestRepository::class => $requests,
            PdoRequestRepository::class => $requests,
            PrivateFileStore::class => $files,
            CreateRequest::class => new CreateRequest($requests, $files),
            ListOwnRequests::class => new ListOwnRequests($requests),
            GetOwnRequest::class => new GetOwnRequest($requests),
            ManagementRepository::class => $management,
            PdoManagementRepository::class => $management,
            ViewAllRequests::class => new ViewAllRequests($management),
            GetRequestForManagement::class => new GetRequestForManagement($management),
            ClassifyRequest::class => new ClassifyRequest($management),
            AssignRequest::class => new AssignRequest($management),
        ]);
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

/** @template T of object */
final class SimpleContainer
{
    /** @param array<class-string, object> $services */
    public function __construct(private readonly array $services)
    {
    }

    /** @template S of object @param class-string<S> $id @return S */
    public function get(string $id): object
    {
        $service = $this->services[$id] ?? null;
        if ($service === null) {
            throw new RuntimeException("Service not configured: {$id}");
        }

        return $service;
    }
}

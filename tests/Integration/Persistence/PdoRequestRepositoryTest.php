<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\MigrationRunner;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoRequestRepositoryTest extends TestCase
{
    public function testCreatesUniqueCodesAndKeepsRequestsPrivateToOwner(): void
    {
        $container = ContainerFactory::build();
        $container->get(MigrationRunner::class)->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
        $pdo = $container->get(PDO::class);
        $repository = $container->get(PdoRequestRepository::class);
        $roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'worker'")->fetchColumn();
        $areaId = $repository->activeAreas()[0]['id'];
        $users = [];
        $codes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $statement = $pdo->prepare('INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES (:role, :area, :email, :name, :hash) RETURNING id');
                $statement->execute(['role' => $roleId, 'area' => $areaId, 'email' => 'sprint2-' . bin2hex(random_bytes(8)) . '@example.test', 'name' => 'Test Worker', 'hash' => 'test-hash']);
                $users[] = (int) $statement->fetchColumn();
            }
            $key = bin2hex(random_bytes(24));
            $codes[] = $repository->create($users[0], $areaId, 'Problema de red', 'No funciona la red en la oficina.', [
                ['key' => $key, 'name' => 'red.pdf', 'mime' => 'application/pdf', 'size' => 128],
            ]);
            $codes[] = $repository->create($users[0], $areaId, 'Problema de impresión', 'La impresora no responde al enviar documentos.', []);
            self::assertNotSame($codes[0], $codes[1]);
            self::assertMatchesRegularExpression('/^SOL-[0-9]{4}-[0-9]{6,}$/', $codes[0]);
            self::assertSame(2, $repository->countOwn($users[0]));
            self::assertSame(0, $repository->countOwn($users[1]));
            self::assertCount(2, $repository->listOwn($users[0], 10, 0));
            self::assertNotNull($repository->findOwn($codes[0], $users[0]));
            self::assertNull($repository->findOwn($codes[0], $users[1]));
            $attachments = $repository->attachmentsForOwn($codes[0], $users[0]);
            self::assertCount(1, $attachments);
            self::assertSame([], $repository->attachmentsForOwn($codes[0], $users[1]));
            self::assertSame($key, $repository->attachmentForOwn((int) $attachments[0]['id'], $users[0])['storage_key']);
            self::assertNull($repository->attachmentForOwn((int) $attachments[0]['id'], $users[1]));
        } finally {
            foreach ($codes as $code) {
                $statement = $pdo->prepare('DELETE FROM requests WHERE public_code = :code');
                $statement->execute(['code' => $code]);
            }
            foreach ($users as $id) {
                $statement = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $statement->execute(['id' => $id]);
            }
        }
    }
}

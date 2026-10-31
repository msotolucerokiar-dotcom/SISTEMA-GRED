<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Support\CreateRequest;
use App\Application\Support\InvalidRequest;
use App\Domain\Support\PrivateFileStore;
use App\Domain\Support\RequestRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CreateRequestTest extends TestCase
{
    public function testRejectsInvalidAreaAndDescriptionWithoutSaving(): void
    {
        $repository = new FakeRequestRepository();
        $useCase = new CreateRequest($repository, new FakeFileStore());
        try {
            $useCase->execute(5, 999, 'Red lenta', 'corta', []);
            self::fail('Expected validation error');
        } catch (InvalidRequest $exception) {
            self::assertArrayHasKey('area', $exception->errors);
            self::assertArrayHasKey('description', $exception->errors);
            self::assertSame(0, $repository->created);
        }
    }

    public function testRejectsContentThatOnlyPretendsToBePdf(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gred');
        file_put_contents($path, 'This is plain text');
        try {
            $file = ['name' => 'evidence.pdf', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
            $this->expectException(InvalidRequest::class);
            (new CreateRequest(new FakeRequestRepository(), new FakeFileStore()))->execute(5, 1, 'Problema red', 'No funciona la red de la oficina.', [$file]);
        } finally {
            unlink($path);
        }
    }

    public function testRejectsTooManyOrOversizedFiles(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gred');
        file_put_contents($path, "%PDF-1.4\n%%EOF");
        try {
            $file = ['name' => 'evidence.pdf', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
            $useCase = new CreateRequest(new FakeRequestRepository(), new FakeFileStore());
            try {
                $useCase->execute(5, 1, 'Problema red', 'No funciona la red de la oficina.', [$file, $file, $file, $file]);
                self::fail('Expected file count error');
            } catch (InvalidRequest $exception) {
                self::assertArrayHasKey('files', $exception->errors);
            }
            $file['size'] = 8388609;
            try {
                $useCase->execute(5, 1, 'Problema red', 'No funciona la red de la oficina.', [$file]);
                self::fail('Expected size error');
            } catch (InvalidRequest $exception) {
                self::assertArrayHasKey('files', $exception->errors);
            }
        } finally {
            unlink($path);
        }
    }

    public function testRemovesStoredFilesWhenDatabaseFails(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gred');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $repository = new FakeRequestRepository();
        $repository->fail = true;
        $store = new FakeFileStore();
        try {
            $file = ['name' => 'evidence.pdf', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
            $this->expectException(RuntimeException::class);
            (new CreateRequest($repository, $store))->execute(5, 1, 'Problema red', 'No funciona la red de la oficina.', [$file]);
        } finally {
            self::assertSame(['stored-key'], $store->deleted);
            unlink($path);
        }
    }
}

final class FakeRequestRepository implements RequestRepository
{
    public int $created = 0;
    public bool $fail = false;
    public function activeAreas(): array { return [['id' => 1, 'name' => 'Sede central']]; }
    public function create(int $requesterId, int $areaId, string $title, string $description, array $attachments): string
    {
        $this->created++;
        if ($this->fail) { throw new RuntimeException('Database failure'); }
        return 'SOL-2026-000001';
    }
    public function listOwn(int $requesterId, int $limit, int $offset): array { return []; }
    public function countOwn(int $requesterId): int { return 0; }
    public function countOwnByStatus(int $requesterId): array { return []; }
    public function findOwn(string $code, int $requesterId): ?array { return null; }
    public function attachmentsForOwn(string $code, int $requesterId): array { return []; }
    public function attachmentForOwn(int $attachmentId, int $requesterId): ?array { return null; }
}

final class FakeFileStore implements PrivateFileStore
{
    public array $deleted = [];
    public function put(string $temporaryPath): string { return 'stored-key'; }
    public function delete(string $key): void { $this->deleted[] = $key; }
    public function path(string $key): string { return $key; }
}

<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\PrivateFileStore;
use App\Domain\Support\RequestRepository;
use finfo;
use Throwable;

final class CreateRequest
{
    private const TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private readonly RequestRepository $requests, private readonly PrivateFileStore $files)
    {
    }

    /** @param list<array{name:string,tmp_name:string,error:int,size:int}> $uploads */
    public function execute(int $requesterId, int $areaId, string $title, string $description, array $uploads): string
    {
        $title = trim($title);
        $description = trim($description);
        $errors = [];
        if (mb_strlen($title) < 5 || mb_strlen($title) > 180) {
            $errors['title'] = 'Escribe un asunto de 5 a 180 caracteres.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 5000) {
            $errors['description'] = 'Describe el problema con 10 a 5000 caracteres.';
        }
        if ($areaId < 1 || !in_array($areaId, array_column($this->requests->activeAreas(), 'id'), true)) {
            $errors['area'] = 'Selecciona un área válida.';
        }
        if (count($uploads) > 3) {
            $errors['files'] = 'Adjunta como máximo 3 archivos.';
        }

        $validated = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($uploads as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || ($file['size'] ?? 0) < 1 || $file['size'] > 8388608
                || !is_file($file['tmp_name'] ?? '')
                || filesize($file['tmp_name']) !== $file['size']) {
                $errors['files'] = 'Cada archivo debe ser válido y no superar 8 MiB.';
                break;
            }
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, self::TYPES, true)) {
                $errors['files'] = 'Solo se permiten PDF e imágenes JPG, PNG o WebP.';
                break;
            }
            $name = basename(str_replace('\\', '/', $file['name']));
            if ($name === '') {
                $errors['files'] = 'Cada archivo debe tener un nombre válido.';
                break;
            }
            $validated[] = ['tmp' => $file['tmp_name'], 'name' => mb_substr($name, 0, 255), 'mime' => $mime, 'size' => $file['size']];
        }
        if ($errors !== []) {
            throw new InvalidRequest($errors);
        }

        $stored = [];
        try {
            foreach ($validated as $file) {
                $key = $this->files->put($file['tmp']);
                $stored[] = ['key' => $key, 'name' => $file['name'], 'mime' => $file['mime'], 'size' => $file['size']];
            }
            return $this->requests->create($requesterId, $areaId, $title, $description, $stored);
        } catch (Throwable $exception) {
            foreach ($stored as $file) {
                $this->files->delete($file['key']);
            }
            throw $exception;
        }
    }
}

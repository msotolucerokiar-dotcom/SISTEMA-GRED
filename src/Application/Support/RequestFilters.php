<?php

declare(strict_types=1);

namespace App\Application\Support;

use DateTimeImmutable;

final class RequestFilters
{
    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function fromInput(array $input, bool $report = false): array
    {
        $errors = [];
        $filters = ['search' => '', 'status' => null, 'area' => null, 'category' => null, 'technician' => null, 'from' => null, 'to' => null];
        $search = is_string($input['search'] ?? null) ? trim($input['search']) : '';
        if (mb_strlen($search) > 100) { $errors['search'] = 'La búsqueda admite hasta 100 caracteres.'; }
        $filters['search'] = $search;
        foreach (['status', 'area', 'category', 'technician'] as $key) {
            $value = $input[$key] ?? null;
            if ($value === null || $value === '') { continue; }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) { $errors[$key] = 'Selecciona una opción válida.'; }
            else { $filters[$key] = (int) $id; }
        }
        foreach (['from', 'to'] as $key) {
            $value = $input[$key] ?? null;
            if ($value === null || $value === '') { continue; }
            $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || $date === false || $date->format('Y-m-d') !== $value) {
                $errors[$key] = 'Escribe una fecha válida (AAAA-MM-DD).';
            } else { $filters[$key] = $value; }
        }
        if ($filters['from'] !== null && $filters['to'] !== null && $filters['from'] > $filters['to']) {
            $errors['to'] = 'La fecha final debe ser igual o posterior a la inicial.';
        }
        if ($report && $errors === []) {
            $today = new DateTimeImmutable('today', new \DateTimeZone('America/Lima'));
            $filters['to'] ??= $today->format('Y-m-d');
            $filters['from'] ??= (new DateTimeImmutable($filters['to']))->modify('-12 months')->format('Y-m-d');
            if ((new DateTimeImmutable($filters['from']))->modify('+12 months') < new DateTimeImmutable($filters['to'])) {
                $errors['from'] = 'El reporte admite un rango máximo de 12 meses.';
            }
        }
        if ($errors !== []) { throw new InvalidRequest($errors); }
        return $filters;
    }
}

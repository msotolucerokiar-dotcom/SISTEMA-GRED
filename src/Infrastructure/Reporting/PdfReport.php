<?php

declare(strict_types=1);

namespace App\Infrastructure\Reporting;

final class PdfReport
{
    /** @param iterable<array<string,mixed>> $rows @param list<array{code:string,name:string,total:int}> $statuses */
    public function render(iterable $rows, int $total, array $statuses, string $from, string $to): string
    {
        $pages = [];
        $lines = [
            'CSI GRED Lambayeque - Reporte de solicitudes',
            'Periodo: ' . $from . ' al ' . $to . ' | Total: ' . $total,
            'Por estado: ' . implode(' | ', array_map(static fn (array $item): string => $item['name'] . ' ' . $item['total'], $statuses)),
            '',
            'Codigo                 Fecha        Estado            Solicitante / Asunto',
        ];
        foreach ($rows as $row) {
            if (count($lines) >= 50) {
                $pages[] = $lines;
                $lines = ['CSI GRED Lambayeque - Reporte de solicitudes (continuación)', '', 'Codigo                 Fecha        Estado            Solicitante / Asunto'];
            }
            $lines[] = sprintf('%-22s %-12s %-17s %s',
                $this->short((string) $row['public_code'], 22),
                substr((string) $row['created_at'], 0, 10),
                $this->short((string) $row['status_name'], 17),
                $this->short((string) $row['requester_name'] . ' / ' . (string) $row['title'], 43)
            );
        }
        $pages[] = $lines;
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>'];
        $pageIds = [];
        $nextId = 4;
        foreach ($pages as $pageNumber => $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId . ' 0 R';
            $content = "BT /F1 9 Tf 40 800 Td 13 TL\n";
            foreach ($pageLines as $line) { $content .= '(' . $this->pdfText($line) . ") Tj T*\n"; }
            $content .= '(Pagina ' . ($pageNumber + 1) . ' de ' . count($pages) . ") Tj\nET";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pages) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (max(array_keys($objects)) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= max(array_keys($objects)); $id++) { $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n"; }
        return $pdf . "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    }

    private function short(string $value, int $max): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return mb_strimwidth($value, 0, $max, '…');
    }

    private function pdfText(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded === false ? '' : $encoded);
    }
}

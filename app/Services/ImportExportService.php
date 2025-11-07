<?php
namespace App\Services;

class ImportExportService
{
    public function exportCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        return stream_get_contents($fh) ?: '';
    }
}

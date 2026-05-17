<?php

namespace App\Domain\Reports\Exports;

use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class XlsxFromCsvExporter
{
    public function export(string $csv, string $sheetName): string
    {
        Log::info('xlsx_export.start', [
            'sheet' => $sheetName,
        ]);

        $path = $this->temporaryPath();
        $rows = 0;

        try {
            $reader = Reader::createFromString($csv);
            $writer = new Writer;
            $writer->openToFile($path);
            $writer->getCurrentSheet()->setName($this->sanitizeSheetName($sheetName));

            foreach ($reader->getRecords() as $record) {
                $writer->addRow(Row::fromValues(array_values($record)));
                $rows++;
            }

            $writer->close();

            $xlsx = file_get_contents($path);

            if ($xlsx === false) {
                throw new RuntimeException('Nie udało się odczytać wygenerowanego XLSX.');
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        Log::info('xlsx_export.success', [
            'sheet' => $sheetName,
            'rows' => $rows,
        ]);

        return $xlsx;
    }

    private function temporaryPath(): string
    {
        $basePath = tempnam(sys_get_temp_dir(), 'sbo-xlsx-');

        if ($basePath === false) {
            throw new RuntimeException('Nie udało się utworzyć pliku tymczasowego XLSX.');
        }

        if (is_file($basePath)) {
            unlink($basePath);
        }

        return $basePath.'.xlsx';
    }

    private function sanitizeSheetName(string $sheetName): string
    {
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $sheetName);
        $name = trim($name);

        if ($name === '') {
            return 'Raport';
        }

        return mb_substr($name, 0, 31);
    }
}

<?php

namespace App\Domain\Reports\Enums;

enum ReportExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function extension(): string
    {
        return $this->value;
    }
}

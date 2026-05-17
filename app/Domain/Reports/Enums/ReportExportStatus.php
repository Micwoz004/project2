<?php

namespace App\Domain\Reports\Enums;

enum ReportExportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}

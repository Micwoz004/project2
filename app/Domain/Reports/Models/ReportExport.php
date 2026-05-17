<?php

namespace App\Domain\Reports\Models;

use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'report' => AdminReportType::class,
            'format' => ReportExportFormat::class,
            'status' => ReportExportStatus::class,
            'context' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }
}

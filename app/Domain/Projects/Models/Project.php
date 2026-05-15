<?php

namespace App\Domain\Projects\Models;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Models\BoardVoteRejection;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\FormalVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\ProjectBoardVote;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'map_data' => 'array',
            'cost_formatted' => 'decimal:2',
            'is_support_list' => 'boolean',
            'need_correction' => 'boolean',
            'need_pre_verification' => 'boolean',
            'small' => 'boolean',
            'is_rejection_accepted' => 'boolean',
            'is_picked' => 'boolean',
            'is_paper' => 'boolean',
            'attachments_anonymized' => 'boolean',
            'authors' => 'array',
            'plot_type_ids' => 'array',
            'plot_type_other_active' => 'boolean',
            'reverify' => 'boolean',
            'recall_submitted' => 'boolean',
            'sent_to_at' => 'boolean',
            'show_task_coauthors' => 'boolean',
            'author_consultation' => 'boolean',
            'was_rejected' => 'boolean',
            'correction_start_time' => 'datetime',
            'correction_end_time' => 'datetime',
            'consent_to_change' => 'boolean',
            'is_hidden' => 'boolean',
            'submitted_at' => 'datetime',
            'checkout_date_time' => 'datetime',
        ];
    }

    public function budgetEdition(): BelongsTo
    {
        return $this->belongsTo(BudgetEdition::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(ProjectArea::class, 'project_area_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function mainDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'main_department_id');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProjectCostItem::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function coauthors(): HasMany
    {
        return $this->hasMany(ProjectCoauthor::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(ProjectCorrection::class);
    }

    public function formalVerifications(): HasMany
    {
        return $this->hasMany(FormalVerification::class);
    }

    public function initialMeritVerifications(): HasMany
    {
        return $this->hasMany(InitialMeritVerification::class);
    }

    public function finalMeritVerifications(): HasMany
    {
        return $this->hasMany(FinalMeritVerification::class);
    }

    public function consultationVerifications(): HasMany
    {
        return $this->hasMany(ConsultationVerification::class);
    }

    public function verificationAssignments(): HasMany
    {
        return $this->hasMany(VerificationAssignment::class);
    }

    public function boardVotes(): HasMany
    {
        return $this->hasMany(ProjectBoardVote::class);
    }

    public function boardVoteRejections(): HasMany
    {
        return $this->hasMany(BoardVoteRejection::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false)
            ->whereIn('status', [
                ProjectStatus::Picked->value,
                ProjectStatus::PickedForRealization->value,
                ProjectStatus::TeamAccepted->value,
                ProjectStatus::MeritVerificationAccepted->value,
            ]);
    }

    public function scopePickedForVoting(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Picked->value);
    }

    public function publicStatusLabel(): string
    {
        return $this->status->publicLabel();
    }
}

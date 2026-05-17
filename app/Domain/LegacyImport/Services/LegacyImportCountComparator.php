<?php

namespace App\Domain\LegacyImport\Services;

use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Models\ProjectDepartmentRecommendation;
use App\Domain\Verification\Models\ProjectDepartmentScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LegacyImportCountComparator
{
    /**
     * @var array<string, array{target_table: string, legacy_column?: string|null, where?: array<string, mixed>, source_valid_project?: bool, source_valid_user?: bool, source_user_column?: string}>
     */
    private const DIRECT_MAPPINGS = [
        'departments' => ['target_table' => 'departments'],
        'users' => ['target_table' => 'users'],
        'taskgroups' => ['target_table' => 'budget_editions'],
        'settings' => ['target_table' => 'application_settings'],
        'pages' => ['target_table' => 'content_pages'],
        'statuses' => ['target_table' => 'project_status_labels'],
        'activations' => ['target_table' => 'user_activation_tokens'],
        'pesel' => ['target_table' => 'legacy_pesel_records'],
        'verification' => ['target_table' => 'legacy_pesel_verification_entries', 'legacy_column' => null],
        'tasktypes' => ['target_table' => 'project_areas'],
        'categories' => ['target_table' => 'categories'],
        'tasks' => ['target_table' => 'projects'],
        'logs' => ['target_table' => 'legacy_audit_logs'],
        'taskscategories' => ['target_table' => 'category_project', 'legacy_column' => null],
        'taskcosts' => ['target_table' => 'project_cost_items'],
        'files' => ['target_table' => 'project_files', 'where' => ['is_private' => false]],
        'filesprivate' => ['target_table' => 'project_files', 'where' => ['is_private' => true]],
        'cocreators' => ['target_table' => 'project_coauthors'],
        'taskverification' => ['target_table' => 'formal_verifications'],
        'taskinitialmeritverification' => ['target_table' => 'initial_merit_verifications'],
        'taskfinishmeritverification' => ['target_table' => 'final_merit_verifications', 'source_valid_project' => true],
        'taskconsultation' => ['target_table' => 'consultation_verifications'],
        'detailedverification' => ['target_table' => 'detailed_verifications'],
        'locationverification' => ['target_table' => 'location_verifications'],
        'verificationversion' => ['target_table' => 'verification_versions'],
        'taskadvancedverification' => ['target_table' => 'advanced_verifications'],
        'prerecommendations' => [
            'target_table' => 'project_department_recommendations',
            'legacy_column' => 'legacy_id',
            'where' => ['legacy_table' => 'prerecommendations', 'type' => ProjectDepartmentRecommendation::TYPE_PRE],
        ],
        'recommendationswjo' => [
            'target_table' => 'project_department_recommendations',
            'legacy_column' => 'legacy_id',
            'where' => ['legacy_table' => 'recommendationswjo', 'type' => ProjectDepartmentRecommendation::TYPE_WJO],
        ],
        'tasksinitialverification' => [
            'target_table' => 'project_department_scopes',
            'legacy_column' => null,
            'where' => ['scope' => ProjectDepartmentScope::SCOPE_INITIAL],
        ],
        'tasksdepartments' => [
            'target_table' => 'project_department_scopes',
            'legacy_column' => null,
            'where' => ['scope' => ProjectDepartmentScope::SCOPE_DEPARTMENT],
        ],
        'coordinatorassignment' => [
            'target_table' => 'project_user_assignments',
            'legacy_column' => 'legacy_id',
            'where' => ['legacy_table' => 'coordinatorassignment'],
        ],
        'verifierassignment' => [
            'target_table' => 'project_user_assignments',
            'legacy_column' => 'legacy_id',
            'where' => ['legacy_table' => 'verifierassignment'],
        ],
        'taskdepartmentassignment' => ['target_table' => 'verification_assignments'],
        'verificationpressure' => ['target_table' => 'verification_pressure_logs'],
        'zkvotes' => ['target_table' => 'project_board_votes', 'where' => ['board_type' => BoardType::Zk->value], 'source_valid_user' => true],
        'otvotes' => ['target_table' => 'project_board_votes', 'where' => ['board_type' => BoardType::Ot->value], 'source_valid_user' => true],
        'atvotes' => ['target_table' => 'project_board_votes', 'where' => ['board_type' => BoardType::At->value], 'source_valid_user' => true],
        'atotvotesrejection' => ['target_table' => 'board_vote_rejections', 'source_valid_user' => true, 'source_user_column' => 'createdBy'],
        'taskappealagainstdecision' => ['target_table' => 'project_appeals'],
        'correspondence' => ['target_table' => 'correspondence_messages'],
        'taskcomments' => ['target_table' => 'project_comments'],
        'comments' => ['target_table' => 'project_public_comments'],
        'notification' => ['target_table' => 'project_notifications'],
        'maillogs' => ['target_table' => 'mail_logs'],
        'taskcorrection' => ['target_table' => 'project_corrections'],
        'taskchangessuggestion' => ['target_table' => 'project_change_suggestions'],
        'versions' => ['target_table' => 'project_versions'],
        'newverification' => ['target_table' => 'voter_registry_hashes'],
        'votingtokens' => ['target_table' => 'voting_tokens'],
        'voters' => ['target_table' => 'voters'],
        'smslogs' => ['target_table' => 'sms_logs'],
        'votecards' => ['target_table' => 'vote_cards'],
        'votes' => ['target_table' => 'votes'],
    ];

    private const SKIPPED_SOURCE_TABLES = [
        'authitem' => 'RBAC import maps roles, permissions and inherited permissions into Spatie tables.',
        'authitemchild' => 'RBAC inheritance is flattened into role_has_permissions.',
        'authassignment' => 'Assignments are split between role and direct permission pivots.',
    ];

    /**
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function compare(string $connection): array
    {
        Log::info('legacy_import_counts.compare.start', [
            'connection' => $connection,
            'tables_count' => count(self::DIRECT_MAPPINGS) + count(self::SKIPPED_SOURCE_TABLES),
        ]);

        $rows = [];

        foreach (self::DIRECT_MAPPINGS as $legacyTable => $mapping) {
            $rows[] = $this->compareDirectMapping($connection, $legacyTable, $mapping);
        }

        foreach (self::SKIPPED_SOURCE_TABLES as $legacyTable => $reason) {
            $rows[] = $this->skippedRow($connection, $legacyTable, $reason);
        }

        $summary = $this->summary($rows);

        Log::info('legacy_import_counts.compare.success', [
            'connection' => $connection,
            'matched_count' => $summary['matched'],
            'mismatched_count' => $summary['mismatched'],
            'missing_source_count' => $summary['missing_source'],
            'missing_target_count' => $summary['missing_target'],
            'skipped_count' => $summary['skipped'],
        ]);

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, int>  $sourceCounts
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function compareSourceCounts(array $sourceCounts, string $source): array
    {
        Log::info('legacy_import_counts.compare_source_counts.start', [
            'source' => $source,
            'tables_count' => count(self::DIRECT_MAPPINGS) + count(self::SKIPPED_SOURCE_TABLES),
        ]);

        $rows = [];

        foreach (self::DIRECT_MAPPINGS as $legacyTable => $mapping) {
            $rows[] = $this->compareDirectMappingSourceCount($sourceCounts, $legacyTable, $mapping);
        }

        foreach (self::SKIPPED_SOURCE_TABLES as $legacyTable => $reason) {
            $rows[] = $this->skippedSourceCountRow($sourceCounts, $legacyTable, $reason);
        }

        $summary = $this->summary($rows);

        Log::info('legacy_import_counts.compare_source_counts.success', [
            'source' => $source,
            'matched_count' => $summary['matched'],
            'mismatched_count' => $summary['mismatched'],
            'missing_source_count' => $summary['missing_source'],
            'missing_target_count' => $summary['missing_target'],
            'skipped_count' => $summary['skipped'],
        ]);

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{target_table: string, legacy_column?: string|null, where?: array<string, mixed>, source_valid_project?: bool, source_valid_user?: bool, source_user_column?: string}  $mapping
     * @return array<string, mixed>
     */
    private function compareDirectMapping(string $connection, string $legacyTable, array $mapping): array
    {
        $sourceCount = $this->sourceCount(
            $connection,
            $legacyTable,
            $mapping['source_valid_project'] ?? false,
            $mapping['source_valid_user'] ?? false,
            $mapping['source_user_column'] ?? 'userId',
        );

        return $this->compareDirectMappingCounts($legacyTable, $mapping, $sourceCount);
    }

    /**
     * @param  array{target_table: string, legacy_column?: string|null, where?: array<string, mixed>, source_valid_project?: bool, source_valid_user?: bool, source_user_column?: string}  $mapping
     * @return array<string, mixed>
     */
    private function compareDirectMappingSourceCount(array $sourceCounts, string $legacyTable, array $mapping): array
    {
        return $this->compareDirectMappingCounts(
            $legacyTable,
            $mapping,
            $sourceCounts[$legacyTable] ?? null,
        );
    }

    /**
     * @param  array{target_table: string, legacy_column?: string|null, where?: array<string, mixed>, source_valid_project?: bool, source_valid_user?: bool, source_user_column?: string}  $mapping
     * @return array<string, mixed>
     */
    private function compareDirectMappingCounts(string $legacyTable, array $mapping, ?int $sourceCount): array
    {
        $targetTable = $mapping['target_table'];

        if ($sourceCount === null) {
            return [
                'legacy_table' => $legacyTable,
                'target_table' => $targetTable,
                'source_count' => null,
                'target_count' => null,
                'difference' => null,
                'status' => 'missing_source',
            ];
        }

        $targetCount = $this->targetCount($targetTable, $mapping['legacy_column'] ?? 'legacy_id', $mapping['where'] ?? []);

        if ($targetCount === null) {
            return [
                'legacy_table' => $legacyTable,
                'target_table' => $targetTable,
                'source_count' => $sourceCount,
                'target_count' => null,
                'difference' => null,
                'status' => 'missing_target',
            ];
        }

        return [
            'legacy_table' => $legacyTable,
            'target_table' => $targetTable,
            'source_count' => $sourceCount,
            'target_count' => $targetCount,
            'difference' => $targetCount - $sourceCount,
            'status' => $sourceCount === $targetCount ? 'matched' : 'mismatched',
        ];
    }

    private function sourceCount(
        string $connection,
        string $table,
        bool $onlyRowsWithProject = false,
        bool $onlyRowsWithUser = false,
        string $userColumn = 'userId',
    ): ?int {
        if (! Schema::connection($connection)->hasTable($table)) {
            Log::warning('legacy_import_counts.source_table_missing', [
                'connection' => $connection,
                'table' => $table,
            ]);

            return null;
        }

        $query = DB::connection($connection)->table($table);

        if ($onlyRowsWithProject) {
            $query->join('tasks', "{$table}.taskId", '=', 'tasks.id');
        }

        if ($onlyRowsWithUser) {
            $query->join('users', "{$table}.{$userColumn}", '=', 'users.id');
        }

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function targetCount(string $table, ?string $legacyColumn, array $where): ?int
    {
        if (! Schema::hasTable($table)) {
            Log::warning('legacy_import_counts.target_table_missing', [
                'table' => $table,
            ]);

            return null;
        }

        $query = DB::table($table);
        $this->applyWhere($query, $where);

        if ($legacyColumn !== null) {
            $query->whereNotNull($legacyColumn);
        }

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function applyWhere(Builder $query, array $where): void
    {
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function skippedRow(string $connection, string $legacyTable, string $reason): array
    {
        return [
            'legacy_table' => $legacyTable,
            'target_table' => null,
            'source_count' => $this->sourceCount($connection, $legacyTable),
            'target_count' => null,
            'difference' => null,
            'status' => 'skipped',
            'reason' => $reason,
        ];
    }

    /**
     * @param  array<string, int>  $sourceCounts
     * @return array<string, mixed>
     */
    private function skippedSourceCountRow(array $sourceCounts, string $legacyTable, string $reason): array
    {
        return [
            'legacy_table' => $legacyTable,
            'target_table' => null,
            'source_count' => $sourceCounts[$legacyTable] ?? null,
            'target_count' => null,
            'difference' => null,
            'status' => 'skipped',
            'reason' => $reason,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summary(array $rows): array
    {
        $summary = [
            'matched' => 0,
            'mismatched' => 0,
            'missing_source' => 0,
            'missing_target' => 0,
            'skipped' => 0,
        ];

        foreach ($rows as $row) {
            $summary[$row['status']]++;
        }

        $summary['total'] = count($rows);

        return $summary;
    }
}

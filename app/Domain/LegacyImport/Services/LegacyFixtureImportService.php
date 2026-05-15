<?php

namespace App\Domain\LegacyImport\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Communications\Models\CorrespondenceMessage;
use App\Domain\Communications\Models\ProjectComment;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Domain\Projects\Models\ProjectCoauthor;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Domain\Projects\Models\ProjectVersion;
use App\Domain\Settings\Models\ApplicationSetting;
use App\Domain\Settings\Models\ContentPage;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Models\BoardVoteRejection;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\FormalVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\ProjectBoardVote;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Enums\VotingTokenType;
use App\Domain\Voting\Models\SmsLog;
use App\Domain\Voting\Models\Vote;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Domain\Voting\Models\VoterRegistryHash;
use App\Domain\Voting\Models\VotingToken;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;

class LegacyFixtureImportService
{
    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     */
    public function import(array $payload, string $source = 'fixture'): LegacyImportBatch
    {
        Log::info('legacy_import.fixture.start', [
            'source' => $source,
        ]);

        return DB::transaction(function () use ($payload, $source): LegacyImportBatch {
            $batch = LegacyImportBatch::query()->create([
                'source_path' => $source,
                'started_at' => now(),
            ]);

            $stats = [
                'taskgroups' => $this->importTaskGroups($payload['taskgroups'] ?? []),
                'settings' => $this->importSettings($payload['settings'] ?? []),
                'pages' => $this->importPages($payload['pages'] ?? []),
                'tasktypes' => $this->importTaskTypes($payload['tasktypes'] ?? []),
                'categories' => $this->importCategories($payload['categories'] ?? []),
                'tasks' => $this->importTasks($payload['tasks'] ?? []),
                'taskscategories' => $this->importTasksCategories($payload['taskscategories'] ?? []),
                'taskcosts' => $this->importTaskCosts($payload['taskcosts'] ?? []),
                'files' => $this->importFiles($payload['files'] ?? [], false),
                'filesprivate' => $this->importFiles($payload['filesprivate'] ?? [], true),
                'cocreators' => $this->importCoauthors($payload['cocreators'] ?? []),
                'taskverification' => $this->importVerificationRows($payload['taskverification'] ?? [], FormalVerification::class),
                'taskinitialmeritverification' => $this->importVerificationRows($payload['taskinitialmeritverification'] ?? [], InitialMeritVerification::class),
                'taskfinishmeritverification' => $this->importVerificationRows($payload['taskfinishmeritverification'] ?? [], FinalMeritVerification::class),
                'taskconsultation' => $this->importVerificationRows($payload['taskconsultation'] ?? [], ConsultationVerification::class),
                'taskdepartmentassignment' => $this->importVerificationAssignments($payload['taskdepartmentassignment'] ?? []),
                'zkvotes' => $this->importBoardVotes($payload['zkvotes'] ?? [], BoardType::Zk),
                'otvotes' => $this->importBoardVotes($payload['otvotes'] ?? [], BoardType::Ot),
                'atvotes' => $this->importBoardVotes($payload['atvotes'] ?? [], BoardType::At),
                'atotvotesrejection' => $this->importBoardVoteRejections($payload['atotvotesrejection'] ?? []),
                'correspondence' => $this->importCorrespondence($payload['correspondence'] ?? []),
                'taskcomments' => $this->importProjectComments($payload['taskcomments'] ?? []),
                'taskcorrection' => $this->importProjectCorrections($payload['taskcorrection'] ?? []),
                'taskchangessuggestion' => $this->importProjectChangeSuggestions($payload['taskchangessuggestion'] ?? []),
                'versions' => $this->importProjectVersions($payload['versions'] ?? []),
                'newverification' => $this->importVoterRegistryHashes($payload['newverification'] ?? []),
                'votingtokens' => $this->importVotingTokens($payload['votingtokens'] ?? []),
                'voters' => $this->importVoters($payload['voters'] ?? []),
                'smslogs' => $this->importSmsLogs($payload['smslogs'] ?? []),
                'votecards' => $this->importVoteCards($payload['votecards'] ?? []),
                'votes' => $this->importVotes($payload['votes'] ?? []),
            ];

            $batch->forceFill([
                'stats' => $stats,
                'finished_at' => now(),
            ])->save();

            Log::info('legacy_import.fixture.success', [
                'source' => $source,
                'batch_id' => $batch->id,
                'tables_count' => count($stats),
            ]);

            return $batch->refresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskGroups(array $rows): int
    {
        foreach ($rows as $row) {
            BudgetEdition::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'propose_start' => Arr::get($row, 'proposeStart'),
                'propose_end' => Arr::get($row, 'proposeEnd'),
                'pre_voting_verification_end' => Arr::get($row, 'preVotingVerificationEnd'),
                'voting_start' => Arr::get($row, 'votingStart'),
                'voting_end' => Arr::get($row, 'votingEnd'),
                'post_voting_verification_end' => Arr::get($row, 'postVotingVerificationEnd'),
                'result_announcement_end' => Arr::get($row, 'resultAnnouncementEnd'),
                'current_digital_card_no' => (int) Arr::get($row, 'currentDigitalCardNo', 0),
                'current_paper_card_no' => (int) Arr::get($row, 'currentPaperCardNo', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importSettings(array $rows): int
    {
        foreach ($rows as $row) {
            ApplicationSetting::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'category' => Arr::get($row, 'category', 'system'),
                'key' => Arr::get($row, 'key'),
                'value' => Arr::get($row, 'value'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importPages(array $rows): int
    {
        foreach ($rows as $row) {
            $budgetEdition = $this->budgetEdition((int) Arr::get($row, 'taskGroupId'));

            ContentPage::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'budget_edition_id' => $budgetEdition->id,
                'symbol' => Arr::get($row, 'symbol'),
                'body' => Arr::get($row, 'body', ''),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskTypes(array $rows): int
    {
        foreach ($rows as $row) {
            ProjectArea::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'name' => Arr::get($row, 'name'),
                'symbol' => Arr::get($row, 'symbol'),
                'name_shortcut' => Arr::get($row, 'nameShortcut'),
                'is_local' => (bool) Arr::get($row, 'local', true),
                'cost_limit' => (int) Arr::get($row, 'costLimit', 0),
                'cost_limit_small' => Arr::get($row, 'costLimitSmall', 0),
                'cost_limit_big' => Arr::get($row, 'costLimitBig', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importCategories(array $rows): int
    {
        foreach ($rows as $row) {
            Category::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'name' => Arr::get($row, 'name'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTasks(array $rows): int
    {
        foreach ($rows as $row) {
            $budgetEdition = $this->budgetEdition((int) Arr::get($row, 'taskGroupId'));
            $area = $this->projectArea((int) Arr::get($row, 'taskTypeId'));
            $category = $this->category((int) Arr::get($row, 'categoryId'));

            Project::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'budget_edition_id' => $budgetEdition->id,
                'project_area_id' => $area->id,
                'category_id' => $category->id,
                'number' => Arr::get($row, 'number'),
                'number_drawn' => Arr::get($row, 'numberDrawn'),
                'title' => Arr::get($row, 'title'),
                'localization' => Arr::get($row, 'localization'),
                'description' => Arr::get($row, 'description'),
                'goal' => Arr::get($row, 'goal'),
                'argumentation' => Arr::get($row, 'argumentation'),
                'status' => ProjectStatus::from((int) Arr::get($row, 'status')),
                'cost' => Arr::get($row, 'cost'),
                'cost_formatted' => Arr::get($row, 'costFormatted'),
                'is_support_list' => (bool) Arr::get($row, 'isSupportList', false),
                'is_picked' => (bool) Arr::get($row, 'isPicked', false),
                'is_hidden' => (bool) Arr::get($row, 'isHidden', false),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskCosts(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            ProjectCostItem::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'description' => Arr::get($row, 'description'),
                'amount' => Arr::get($row, 'amount', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTasksCategories(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $category = $this->category((int) Arr::get($row, 'categoryId'));

            $project->categories()->syncWithoutDetaching([$category->id]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importFiles(array $rows, bool $private): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            ProjectFile::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'stored_name' => Arr::get($row, 'filename', Arr::get($row, 'storedName')),
                'original_name' => Arr::get($row, 'originalName', Arr::get($row, 'filename')),
                'description' => Arr::get($row, 'description'),
                'type' => ProjectFileType::tryFrom((int) Arr::get($row, 'type', ProjectFileType::Other->value)) ?? ProjectFileType::Other,
                'is_private' => $private,
                'is_task_form_attachment' => (bool) Arr::get($row, 'isTaskFormAttachment', false),
                'is_pre_verification_attachment' => (bool) Arr::get($row, 'isPreVerificationAttachment', false),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importCoauthors(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            ProjectCoauthor::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'first_name' => Arr::get($row, 'firstName'),
                'last_name' => Arr::get($row, 'lastName'),
                'email' => Arr::get($row, 'email'),
                'phone' => Arr::get($row, 'phone'),
                'post_code' => Arr::get($row, 'postCode'),
                'city' => Arr::get($row, 'city'),
                'personal_data_agree' => (bool) Arr::get($row, 'personalDataAgree', false),
                'name_agree' => (bool) Arr::get($row, 'nameAgree', false),
                'data_evaluation_agree' => (bool) Arr::get($row, 'dataEvaluationAgree', false),
                'read_confirm' => (bool) Arr::get($row, 'readConfirm', false),
                'confirm' => (bool) Arr::get($row, 'confirm', false),
                'email_agree' => (bool) Arr::get($row, 'emailAgree', false),
                'phone_agree' => (bool) Arr::get($row, 'phoneAgree', false),
                'hash' => Arr::get($row, 'hash'),
            ]);
        }

        return count($rows);
    }

    /**
     * @template TModel of Model
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  class-string<TModel>  $model
     */
    private function importVerificationRows(array $rows, string $model): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            $model::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'department_id' => $this->optionalDepartmentId(Arr::get($row, 'departmentId')),
                'created_by_id' => $this->optionalUserId(Arr::get($row, 'creatorId')),
                'modified_by_id' => $this->optionalUserId(Arr::get($row, 'modifyingUserId')),
                'status' => (int) Arr::get($row, 'status', 1),
                'result' => Arr::has($row, 'result') ? (bool) Arr::get($row, 'result') : null,
                'result_comments' => Arr::get($row, 'resultComments'),
                'is_public' => (bool) Arr::get($row, 'isPublic', false),
                'answers' => Arr::get($row, 'answers'),
                'raw_legacy_payload' => $row,
                'sent_at' => Arr::get($row, 'sentAt'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVerificationAssignments(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $department = $this->department((int) Arr::get($row, 'departmentId'));

            VerificationAssignment::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'department_id' => $department->id,
                'deadline' => Arr::get($row, 'deadline'),
                'notes' => Arr::get($row, 'notes'),
                'sent_at' => Arr::get($row, 'sentAt'),
                'is_returned' => (bool) Arr::get($row, 'isReturned', false),
                'type' => (int) Arr::get($row, 'type', 1),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importBoardVotes(array $rows, BoardType $boardType): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $user = $this->user((int) Arr::get($row, 'userId'));

            ProjectBoardVote::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'board_type' => $boardType,
                'choice' => (int) Arr::get($row, 'choice', Arr::get($row, 'vote')),
                'comment' => Arr::get($row, 'comment'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importBoardVoteRejections(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $user = $this->user((int) Arr::get($row, 'userId'));

            BoardVoteRejection::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'board_type' => BoardType::from((string) Arr::get($row, 'boardType', BoardType::At->value)),
                'comment' => Arr::get($row, 'comment'),
                'created_by_id' => $user->id,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importCorrespondence(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            CorrespondenceMessage::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'user_id' => $this->optionalUserId(Arr::get($row, 'userId')),
                'message_text' => Arr::get($row, 'messageText', Arr::get($row, 'content')),
                'is_read' => (bool) Arr::get($row, 'isRead', false),
                'read_at' => Arr::get($row, 'readAt'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importProjectComments(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            ProjectComment::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'user_id' => $this->optionalUserId(Arr::get($row, 'userId')),
                'content' => Arr::get($row, 'content', Arr::get($row, 'messageText')),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importProjectCorrections(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $createdAt = Arr::get($row, 'createdAt');

            ProjectCorrection::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'creator_id' => $this->optionalUserId(Arr::get($row, 'creatorId')),
                'allowed_fields' => $this->allowedCorrectionFields($row),
                'notes' => Arr::get($row, 'notes'),
                'correction_deadline' => Arr::get($row, 'correctionDeadline'),
                'correction_done' => (bool) Arr::get($row, 'correctionDone', false),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importProjectChangeSuggestions(array $rows): int
    {
        foreach ($rows as $row) {
            $legacyId = $this->legacyId($row);
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $createdAt = Arr::get($row, 'createdAt');

            ProjectChangeSuggestion::query()->updateOrCreate([
                'legacy_id' => $legacyId,
            ], [
                'project_id' => $project->id,
                'created_by_id' => $this->optionalUserId(Arr::get($row, 'createdBy')),
                'decision_by_id' => $this->optionalUserId(Arr::get($row, 'decisionBy')),
                'old_data' => $this->decodeLegacyJson(Arr::get($row, 'oldData'), 'taskchangessuggestion', $legacyId),
                'old_costs' => $this->decodeLegacyJson(Arr::get($row, 'oldCosts'), 'taskchangessuggestion', $legacyId),
                'old_files' => $this->decodeLegacyJson(Arr::get($row, 'oldFiles'), 'taskchangessuggestion', $legacyId),
                'new_data' => $this->decodeLegacyJson(Arr::get($row, 'newData'), 'taskchangessuggestion', $legacyId),
                'new_costs' => $this->decodeLegacyJson(Arr::get($row, 'newCosts'), 'taskchangessuggestion', $legacyId),
                'new_files' => $this->decodeLegacyJson(Arr::get($row, 'newFiles'), 'taskchangessuggestion', $legacyId),
                'consultation' => Arr::get($row, 'consultation'),
                'author_comment' => Arr::get($row, 'authorComment'),
                'is_accepted_by_admin' => (bool) Arr::get($row, 'isAcceptedByAdmin', false),
                'deadline' => Arr::get($row, 'deadline'),
                'decision' => (int) Arr::get($row, 'decision', 0),
                'decision_at' => $this->nullableLegacyDate(Arr::get($row, 'decisionAt')),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importProjectVersions(array $rows): int
    {
        foreach ($rows as $row) {
            $legacyId = $this->legacyId($row);
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $status = ProjectStatus::tryFrom((int) Arr::get($row, 'status', 0));
            $createdAt = Arr::get($row, 'createTime');

            ProjectVersion::query()->updateOrCreate([
                'legacy_id' => $legacyId,
            ], [
                'project_id' => $project->id,
                'user_id' => $this->optionalUserId(Arr::get($row, 'userId')),
                'status' => $status?->value,
                'data' => $this->decodeLegacyJson(Arr::get($row, 'data'), 'versions', $legacyId),
                'files' => $this->decodeLegacyJson(Arr::get($row, 'files'), 'versions', $legacyId),
                'costs' => $this->decodeLegacyJson(Arr::get($row, 'costs'), 'versions', $legacyId),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVoterRegistryHashes(array $rows): int
    {
        foreach ($rows as $row) {
            VoterRegistryHash::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'hash' => mb_strtoupper((string) Arr::get($row, 'hash')),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVotingTokens(array $rows): int
    {
        foreach ($rows as $row) {
            $createdAt = Arr::get($row, 'createTime');
            $type = VotingTokenType::tryFrom((int) Arr::get($row, 'type', VotingTokenType::Sms->value)) ?? VotingTokenType::Sms;

            VotingToken::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'token' => Arr::get($row, 'token'),
                'pesel' => Arr::get($row, 'pesel'),
                'first_name' => Arr::get($row, 'firstName'),
                'second_name' => Arr::get($row, 'secondName'),
                'mother_last_name' => Arr::get($row, 'motherLastName'),
                'last_name' => Arr::get($row, 'lastName'),
                'email' => Arr::get($row, 'email'),
                'phone' => Arr::get($row, 'phone'),
                'disabled' => (bool) Arr::get($row, 'disabled', false),
                'type' => $type,
                'ip' => Arr::get($row, 'ip'),
                'user_agent' => Arr::get($row, 'userAgent'),
                'extra_data' => [
                    'citizen_confirm' => Arr::get($row, 'citizenConfirm'),
                    'living_address' => Arr::get($row, 'livingAddress'),
                    'school_address' => Arr::get($row, 'schoolAddress'),
                    'study_address' => Arr::get($row, 'studyAddress'),
                    'work_address' => Arr::get($row, 'workAddress'),
                    'parent_name' => Arr::get($row, 'parentName'),
                    'parent_confirm' => (bool) Arr::get($row, 'parentConfirm', false),
                    'statement' => (bool) Arr::get($row, 'statement', false),
                    'city_statement' => (bool) Arr::get($row, 'cityStatement', false),
                    'no_pesel_number' => (bool) Arr::get($row, 'noPeselNumber', false),
                    'father_name' => Arr::get($row, 'fatherName'),
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVoters(array $rows): int
    {
        foreach ($rows as $row) {
            Voter::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'pesel' => Arr::get($row, 'pesel'),
                'first_name' => Arr::get($row, 'firstName'),
                'second_name' => Arr::get($row, 'secondName'),
                'mother_last_name' => Arr::get($row, 'motherLastName'),
                'last_name' => Arr::get($row, 'lastName'),
                'father_name' => Arr::get($row, 'fatherName'),
                'email' => Arr::get($row, 'email'),
                'street' => Arr::get($row, 'street'),
                'house_no' => Arr::get($row, 'houseNo'),
                'flat_no' => Arr::get($row, 'flatNo'),
                'post_code' => Arr::get($row, 'postCode'),
                'city' => Arr::get($row, 'city'),
                'ip' => Arr::get($row, 'ip'),
                'birth_date' => Arr::get($row, 'birthDate'),
                'sex' => Arr::get($row, 'sex'),
                'age' => Arr::get($row, 'age'),
                'user_agent' => Arr::get($row, 'userAgent'),
                'phone' => Arr::get($row, 'phone'),
                'created_at' => Arr::get($row, 'created'),
                'updated_at' => Arr::get($row, 'created'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importSmsLogs(array $rows): int
    {
        foreach ($rows as $row) {
            $createdAt = Arr::get($row, 'created');

            SmsLog::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'phone' => Arr::get($row, 'phone'),
                'ip' => Arr::get($row, 'ip'),
                'voter_id' => $this->optionalVoterId(Arr::get($row, 'voterId')),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVoteCards(array $rows): int
    {
        foreach ($rows as $row) {
            $budgetEdition = $this->budgetEdition((int) Arr::get($row, 'taskGroupId'));
            $voter = $this->voter((int) Arr::get($row, 'voterId'));

            VoteCard::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'budget_edition_id' => $budgetEdition->id,
                'voter_id' => $voter->id,
                'created_by_id' => $this->optionalUserId(Arr::get($row, 'creatorId')),
                'consultant_id' => $this->optionalUserId(Arr::get($row, 'consultantId')),
                'checkout_user_id' => $this->optionalUserId(Arr::get($row, 'checkoutUserId')),
                'statement' => (bool) Arr::get($row, 'statement', false),
                'terms_accepted' => (bool) Arr::get($row, 'termsAccepted', false),
                'city_statement' => (bool) Arr::get($row, 'cityStatement', false),
                'no_pesel_number' => (bool) Arr::get($row, 'noPeselNumber', false),
                'card_no' => Arr::get($row, 'cardNo'),
                'digital' => (bool) Arr::get($row, 'digital', true),
                'status' => VoteCardStatus::from((int) Arr::get($row, 'status')),
                'checkout_date_time' => Arr::get($row, 'checkoutDateTime'),
                'notes' => Arr::get($row, 'notes'),
                'citizen_confirm' => Arr::get($row, 'citizenConfirm'),
                'living_address' => Arr::get($row, 'livingAddress'),
                'school_address' => Arr::get($row, 'schoolAddress'),
                'study_address' => Arr::get($row, 'studyAddress'),
                'work_address' => Arr::get($row, 'workAddress'),
                'parent_name' => Arr::get($row, 'parentName'),
                'parent_confirm' => (bool) Arr::get($row, 'parentConfirm', false),
                'ip' => Arr::get($row, 'ip'),
                'created_at' => Arr::get($row, 'created'),
                'updated_at' => Arr::get($row, 'modified', Arr::get($row, 'created')),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVotes(array $rows): int
    {
        foreach ($rows as $row) {
            $voteCard = $this->voteCard((int) Arr::get($row, 'voteCardId'));
            $voter = $this->voter((int) Arr::get($row, 'voterId'));
            $project = $this->project((int) Arr::get($row, 'taskId'));

            Vote::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'vote_card_id' => $voteCard->id,
                'voter_id' => $voter->id,
                'project_id' => $project->id,
                'points' => (int) Arr::get($row, 'points', 1),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legacyId(array $row): int
    {
        return (int) Arr::get($row, 'id');
    }

    private function budgetEdition(int $legacyId): BudgetEdition
    {
        return $this->findLegacy(BudgetEdition::class, $legacyId, 'taskgroups');
    }

    private function projectArea(int $legacyId): ProjectArea
    {
        return $this->findLegacy(ProjectArea::class, $legacyId, 'tasktypes');
    }

    private function category(int $legacyId): Category
    {
        return $this->findLegacy(Category::class, $legacyId, 'categories');
    }

    private function project(int $legacyId): Project
    {
        return $this->findLegacy(Project::class, $legacyId, 'tasks');
    }

    private function voter(int $legacyId): Voter
    {
        return $this->findLegacy(Voter::class, $legacyId, 'voters');
    }

    private function voteCard(int $legacyId): VoteCard
    {
        return $this->findLegacy(VoteCard::class, $legacyId, 'votecards');
    }

    private function department(int $legacyId): Department
    {
        return $this->findLegacy(Department::class, $legacyId, 'departments');
    }

    private function user(int $legacyId): User
    {
        return $this->findLegacy(User::class, $legacyId, 'users');
    }

    private function optionalDepartmentId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        return Department::query()->where('legacy_id', (int) $legacyId)->value('id');
    }

    private function optionalUserId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        return User::query()->where('legacy_id', (int) $legacyId)->value('id');
    }

    private function optionalVoterId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        return Voter::query()->where('legacy_id', (int) $legacyId)->value('id');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function allowedCorrectionFields(array $row): array
    {
        $fields = [
            'title' => ProjectCorrectionField::Title,
            'taskTypeId' => ProjectCorrectionField::ProjectArea,
            'localization' => ProjectCorrectionField::Localization,
            'mapData' => ProjectCorrectionField::MapData,
            'goal' => ProjectCorrectionField::Goal,
            'description' => ProjectCorrectionField::Description,
            'argumentation' => ProjectCorrectionField::Argumentation,
            'recipients' => ProjectCorrectionField::Recipients,
            'freeOfCharge' => ProjectCorrectionField::FreeOfCharge,
            'cost' => ProjectCorrectionField::Cost,
            'supportAttachment' => ProjectCorrectionField::SupportAttachment,
            'agreementAttachment' => ProjectCorrectionField::AgreementAttachment,
            'mapAttachment' => ProjectCorrectionField::MapAttachment,
            'parentAgreementAttachment' => ProjectCorrectionField::ParentAgreementAttachment,
            'attachments' => ProjectCorrectionField::Attachments,
            'availability' => ProjectCorrectionField::Availability,
            'categoryId' => ProjectCorrectionField::Category,
        ];

        return array_values(array_map(
            static fn (ProjectCorrectionField $field): string => $field->value,
            array_filter(
                $fields,
                static fn (ProjectCorrectionField $field, string $legacyColumn): bool => (bool) Arr::get($row, $legacyColumn, false),
                ARRAY_FILTER_USE_BOTH
            )
        ));
    }

    private function nullableLegacyDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function decodeLegacyJson(mixed $value, string $table, int $legacyId): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        try {
            $decoded = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('legacy_import.fixture.invalid_json', [
                'table' => $table,
                'legacy_id' => $legacyId,
            ]);

            throw new DomainException("Niepoprawny JSON legacy {$table}:{$legacyId}.", previous: $exception);
        }

        if (! is_array($decoded)) {
            Log::warning('legacy_import.fixture.invalid_json_shape', [
                'table' => $table,
                'legacy_id' => $legacyId,
            ]);

            throw new DomainException("Niepoprawny kształt JSON legacy {$table}:{$legacyId}.");
        }

        return $decoded;
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return TModel
     */
    private function findLegacy(string $model, int $legacyId, string $table): mixed
    {
        $record = $model::query()->where('legacy_id', $legacyId)->first();

        if ($record === null) {
            Log::warning('legacy_import.fixture.missing_relation', [
                'table' => $table,
                'legacy_id' => $legacyId,
            ]);

            throw new DomainException("Brak rekordu legacy {$table}:{$legacyId}.");
        }

        return $record;
    }
}

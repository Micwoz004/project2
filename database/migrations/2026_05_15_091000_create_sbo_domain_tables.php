<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->boolean('status')->default(true)->after('password');
            $table->string('pesel', 11)->nullable()->after('status');
            $table->string('first_name', 127)->nullable()->after('pesel');
            $table->string('last_name', 127)->nullable()->after('first_name');
            $table->string('phone', 30)->nullable()->after('last_name');
            $table->string('street', 127)->nullable()->after('phone');
            $table->string('house_no', 20)->nullable()->after('street');
            $table->string('flat_no', 20)->nullable()->after('house_no');
            $table->string('post_code', 6)->nullable()->after('flat_no');
            $table->string('city', 127)->nullable()->after('post_code');
            $table->foreignId('department_id')->nullable()->after('city')->constrained()->nullOnDelete();
            $table->string('department_text', 200)->nullable()->after('department_id');
        });

        Schema::create('budget_editions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->dateTime('propose_start');
            $table->dateTime('propose_end');
            $table->dateTime('pre_voting_verification_end');
            $table->dateTime('voting_start');
            $table->dateTime('voting_end');
            $table->dateTime('post_voting_verification_end');
            $table->dateTime('result_announcement_end');
            $table->unsignedInteger('current_digital_card_no')->default(0);
            $table->unsignedInteger('current_paper_card_no')->default(0);
            $table->boolean('is_project_number_drawing')->default(false);
            $table->timestamps();
        });

        Schema::create('project_areas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->text('name');
            $table->string('symbol', 8);
            $table->string('name_shortcut')->nullable();
            $table->boolean('is_local')->default(true);
            $table->integer('cost_limit')->default(0);
            $table->decimal('cost_limit_small', 12)->default(0);
            $table->decimal('cost_limit_big', 12)->default(0);
            $table->unsignedInteger('current_digital_card_no')->default(0);
            $table->unsignedInteger('current_paper_card_no')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('name', 50);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('budget_edition_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 3);
            $table->longText('body');
            $table->timestamps();
            $table->unique(['budget_edition_id', 'symbol']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('budget_edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('main_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('number')->nullable();
            $table->unsignedInteger('number_drawn')->nullable();
            $table->string('title', 600);
            $table->longText('localization')->nullable();
            $table->string('address', 300)->nullable();
            $table->longText('plot')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->longText('map_lng_lat')->nullable();
            $table->json('map_data')->nullable();
            $table->longText('description')->nullable();
            $table->longText('goal')->nullable();
            $table->longText('argumentation')->nullable();
            $table->longText('availability')->nullable();
            $table->longText('recipients')->nullable();
            $table->longText('free_of_charge')->nullable();
            $table->string('short_description', 700)->nullable();
            $table->string('cost', 1000)->nullable();
            $table->string('additional_cost', 500)->nullable();
            $table->decimal('cost_formatted', 12, 2)->nullable();
            $table->integer('status')->default(1);
            $table->boolean('is_support_list')->default(false);
            $table->boolean('need_correction')->default(false);
            $table->boolean('need_pre_verification')->default(false);
            $table->boolean('small')->default(false);
            $table->boolean('is_rejection_accepted')->default(true);
            $table->boolean('is_picked')->default(false);
            $table->unsignedTinyInteger('local')->nullable();
            $table->boolean('is_paper')->default(false);
            $table->unsignedTinyInteger('contact_with')->nullable();
            $table->boolean('attachments_anonymized')->default(false);
            $table->integer('sort')->nullable();
            $table->foreignId('checkout_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checkout_date_time')->nullable();
            $table->json('authors')->nullable();
            $table->json('plot_type_ids')->nullable();
            $table->string('plot_type_other')->nullable();
            $table->boolean('plot_type_other_active')->default(false);
            $table->boolean('reverify')->default(false);
            $table->boolean('recall_submitted')->default(false);
            $table->boolean('sent_to_at')->default(false);
            $table->boolean('show_task_coauthors')->default(true);
            $table->boolean('author_consultation')->default(false);
            $table->boolean('was_rejected')->default(false);
            $table->longText('author_consultation_notes')->nullable();
            $table->longText('notes')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->longText('zk_move_notes')->nullable();
            $table->longText('verifier_notes')->nullable();
            $table->longText('rejection_at_comment')->nullable();
            $table->longText('rejection_ot_comment')->nullable();
            $table->unsignedInteger('correction_no')->default(0);
            $table->dateTime('correction_start_time')->nullable();
            $table->dateTime('correction_end_time')->nullable();
            $table->boolean('consent_to_change')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['budget_edition_id', 'status']);
            $table->index(['project_area_id', 'number_drawn']);
        });

        Schema::create('category_project', function (Blueprint $table): void {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'category_id']);
        });

        Schema::create('project_cost_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->longText('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('project_files', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('stored_name');
            $table->string('original_name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('type');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_task_form_attachment')->default(false);
            $table->boolean('is_pre_verification_attachment')->default(false);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_coauthors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 127);
            $table->string('last_name', 127);
            $table->string('email', 127)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('post_code', 6)->nullable();
            $table->string('city', 127)->nullable();
            $table->boolean('personal_data_agree')->default(false);
            $table->boolean('name_agree')->default(false);
            $table->boolean('data_evaluation_agree')->default(false);
            $table->boolean('read_confirm')->default(false);
            $table->boolean('confirm')->default(false);
            $table->boolean('email_agree')->default(false);
            $table->boolean('phone_agree')->default(false);
            $table->string('hash')->nullable();
            $table->timestamps();
        });

        Schema::create('project_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('status')->nullable();
            $table->json('data');
            $table->json('files')->nullable();
            $table->json('costs')->nullable();
            $table->timestamps();
        });

        Schema::create('verification_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->dateTime('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->boolean('is_returned')->default(false);
            $table->unsignedTinyInteger('type');
            $table->timestamps();
        });

        foreach ([
            'formal_verifications',
            'initial_merit_verifications',
            'final_merit_verifications',
            'consultation_verifications',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable()->unique();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('modified_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('status')->default(1);
                $table->boolean('result')->nullable();
                $table->longText('result_comments')->nullable();
                $table->boolean('is_public')->default(false);
                $table->json('answers')->nullable();
                $table->json('raw_legacy_payload')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('board_vote_rejections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('board_type', 20);
            $table->longText('comment');
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('project_board_votes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('board_type', 20);
            $table->smallInteger('choice');
            $table->longText('comment')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'user_id', 'board_type']);
        });

        Schema::create('voter_registry_hashes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('voters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('pesel', 11)->nullable()->index();
            $table->string('first_name', 64)->nullable();
            $table->string('second_name', 64)->nullable();
            $table->string('mother_last_name', 64)->nullable();
            $table->string('last_name', 64);
            $table->string('father_name', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('street', 128)->nullable();
            $table->string('house_no', 100)->nullable();
            $table->string('flat_no', 100)->nullable();
            $table->string('post_code', 6)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('ip', 128)->nullable();
            $table->date('birth_date')->nullable();
            $table->char('sex', 1)->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('phone', 18)->nullable();
            $table->timestamps();
        });

        Schema::create('vote_cards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('budget_edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checkout_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('statement')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('city_statement')->default(false);
            $table->boolean('no_pesel_number')->default(false);
            $table->unsignedInteger('card_no')->nullable();
            $table->boolean('digital')->default(true);
            $table->unsignedTinyInteger('status')->default(1);
            $table->dateTime('checkout_date_time')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('citizen_confirm')->nullable();
            $table->string('living_address', 200)->nullable();
            $table->string('school_address', 200)->nullable();
            $table->string('study_address', 200)->nullable();
            $table->string('work_address', 200)->nullable();
            $table->string('parent_name', 200)->nullable();
            $table->boolean('parent_confirm')->default(false);
            $table->string('ip', 128)->nullable();
            $table->timestamps();
            $table->index(['budget_edition_id', 'status']);
        });

        Schema::create('votes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('vote_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('points');
            $table->timestamps();
            $table->unique(['vote_card_id', 'project_id']);
        });

        Schema::create('voting_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('token', 255);
            $table->string('pesel', 11)->nullable()->index();
            $table->string('first_name', 64)->nullable();
            $table->string('second_name', 64)->nullable();
            $table->string('mother_last_name', 64)->nullable();
            $table->string('last_name', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 9)->nullable()->index();
            $table->boolean('disabled')->default(false);
            $table->unsignedTinyInteger('type');
            $table->string('ip', 31)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->index(['id', 'token', 'disabled']);
        });

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('phone', 200);
            $table->string('ip', 200)->nullable();
            $table->foreignId('voter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('category', 64);
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['category', 'key']);
        });

        Schema::create('correspondence_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('message_text');
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('legacy_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path');
            $table->string('checksum', 128)->nullable();
            $table->json('stats')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_batches');
        Schema::dropIfExists('project_comments');
        Schema::dropIfExists('correspondence_messages');
        Schema::dropIfExists('application_settings');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('voting_tokens');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('vote_cards');
        Schema::dropIfExists('voters');
        Schema::dropIfExists('voter_registry_hashes');
        Schema::dropIfExists('project_board_votes');
        Schema::dropIfExists('board_vote_rejections');
        Schema::dropIfExists('consultation_verifications');
        Schema::dropIfExists('final_merit_verifications');
        Schema::dropIfExists('initial_merit_verifications');
        Schema::dropIfExists('formal_verifications');
        Schema::dropIfExists('verification_assignments');
        Schema::dropIfExists('project_versions');
        Schema::dropIfExists('project_coauthors');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_cost_items');
        Schema::dropIfExists('category_project');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('project_areas');
        Schema::dropIfExists('budget_editions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'legacy_id',
                'status',
                'pesel',
                'first_name',
                'last_name',
                'phone',
                'street',
                'house_no',
                'flat_no',
                'post_code',
                'city',
                'department_text',
            ]);
        });

        Schema::dropIfExists('departments');
    }
};

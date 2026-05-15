<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_change_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decision_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('old_data');
            $table->json('old_costs');
            $table->json('old_files');
            $table->json('new_data');
            $table->json('new_costs');
            $table->json('new_files');
            $table->text('consultation')->nullable();
            $table->text('author_comment')->nullable();
            $table->boolean('is_accepted_by_admin')->default(false);
            $table->dateTime('deadline');
            $table->unsignedTinyInteger('decision')->default(0);
            $table->dateTime('decision_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'decision', 'decision_at', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_change_suggestions');
    }
};

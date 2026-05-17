<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_tie_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_edition_id')->constrained()->cascadeOnDelete();
            $table->string('group_key');
            $table->unsignedInteger('points');
            $table->json('project_ids');
            $table->foreignId('winner_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('decided_by_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('decided_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_edition_id', 'group_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_tie_decisions');
    }
};

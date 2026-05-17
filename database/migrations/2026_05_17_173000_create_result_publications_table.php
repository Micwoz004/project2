<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('published_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('projects_count')->default(0);
            $table->json('project_totals');
            $table->json('area_totals');
            $table->json('category_totals');
            $table->json('status_counts');
            $table->json('tie_groups');
            $table->json('category_differences');
            $table->dateTime('published_at');
            $table->timestamps();

            $table->unique(['budget_edition_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publications');
    }
};

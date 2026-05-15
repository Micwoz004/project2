<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detailed_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('answers');
            $table->boolean('has_recommendations')->default(false);
            $table->longText('recommendations')->nullable();
            $table->dateTime('recommendations_at')->nullable();
            $table->unsignedTinyInteger('recommendations_form')->nullable();
            $table->longText('verification_comments')->nullable();
            $table->unsignedTinyInteger('verification_result');
            $table->longText('result_reason')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('location_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('answers');
            $table->boolean('has_recommendations')->default(false);
            $table->longText('recommendations')->nullable();
            $table->dateTime('recommendations_at')->nullable();
            $table->unsignedTinyInteger('recommendations_form')->nullable();
            $table->longText('verification_comments')->nullable();
            $table->unsignedTinyInteger('verification_result');
            $table->longText('result_reason')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('verification_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->unsignedBigInteger('verification_legacy_id')->index();
            $table->unsignedTinyInteger('type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->mediumText('raw_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_versions');
        Schema::dropIfExists('location_verifications');
        Schema::dropIfExists('detailed_verifications');
    }
};

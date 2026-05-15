<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advanced_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('status')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->json('raw_legacy_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advanced_verifications');
    }
};

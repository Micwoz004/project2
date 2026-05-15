<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->string('controller', 150)->nullable();
            $table->string('action', 150)->nullable();
            $table->dateTime('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_audit_logs');
    }
};

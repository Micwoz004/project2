<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_email', 500);
            $table->longText('subject');
            $table->longText('body');
            $table->dateTime('sent_at');
            $table->timestamps();
        });

        Schema::create('mail_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 500);
            $table->string('subject', 500);
            $table->longText('content');
            $table->string('controller', 150)->nullable();
            $table->string('action', 150)->nullable();
            $table->dateTime('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
        Schema::dropIfExists('project_notifications');
    }
};

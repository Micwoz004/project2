<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_appeals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->longText('appeal_message');
            $table->longText('response_to_appeal')->nullable();
            $table->dateTime('response_created_at')->nullable();
            $table->unsignedTinyInteger('first_decision')->default(0);
            $table->dateTime('first_decision_created_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_appeals');
    }
};

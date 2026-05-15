<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_department_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 32);
            $table->dateTime('opinion_deadline')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'department_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_department_scopes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_status_labels', function (Blueprint $table): void {
            $table->id();
            $table->integer('legacy_id')->unique();
            $table->integer('status')->unique();
            $table->string('name', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_status_labels');
    }
};

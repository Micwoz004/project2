<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dictionary_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->string('source_table', 64)->nullable();
            $table->string('kind', 64);
            $table->string('value', 255);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['source_table', 'legacy_id']);
            $table->unique(['kind', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionary_entries');
    }
};

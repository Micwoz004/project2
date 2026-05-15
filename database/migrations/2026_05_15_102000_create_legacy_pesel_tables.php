<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_pesel_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('pesel', 11)->unique();
            $table->string('first_name', 63);
            $table->string('second_name', 63)->nullable();
            $table->string('mother_last_name', 64)->nullable();
            $table->string('last_name', 63);
            $table->string('father_name', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_pesel_verification_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('pesel', 11)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_pesel_verification_entries');
        Schema::dropIfExists('legacy_pesel_records');
    }
};

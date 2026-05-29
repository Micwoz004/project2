<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table): void {
            $table->string('template', 100)->nullable()->after('author_email');
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table): void {
            $table->dropColumn('template');
        });
    }
};

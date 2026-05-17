<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_coauthors', function (Blueprint $table): void {
            $table->string('street', 128)->nullable()->after('phone');
            $table->string('house_no', 20)->nullable()->after('street');
            $table->string('flat_no', 20)->nullable()->after('house_no');
        });
    }

    public function down(): void
    {
        Schema::table('project_coauthors', function (Blueprint $table): void {
            $table->dropColumn([
                'street',
                'house_no',
                'flat_no',
            ]);
        });
    }
};

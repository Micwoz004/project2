<?php

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_editions', function (Blueprint $table): void {
            $table->string('name')->default('Edycja SBO');
            $table->unsignedInteger('year')->default((int) date('Y'));
            $table->string('status', 20)->default(BudgetEditionStatus::Active->value);
        });

        DB::table('budget_editions')
            ->orderBy('id')
            ->get(['id', 'propose_start'])
            ->each(function (object $edition): void {
                $year = Carbon::parse($edition->propose_start)->year;

                DB::table('budget_editions')
                    ->where('id', $edition->id)
                    ->update([
                        'name' => 'Edycja SBO '.$year,
                        'year' => $year,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('budget_editions', function (Blueprint $table): void {
            $table->dropColumn(['name', 'year', 'status']);
        });
    }
};

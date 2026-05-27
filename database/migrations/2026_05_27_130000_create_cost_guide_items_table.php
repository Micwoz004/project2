<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_guide_items', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 180);
            $table->string('price_range', 120);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'sort']);
        });

        $now = now();
        DB::table('cost_guide_items')->insert([
            [
                'label' => 'Stojak rowerowy',
                'price_range' => '700-1 500 zł',
                'is_published' => true,
                'sort' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Ławka z montażem',
                'price_range' => '2-6 tys. zł',
                'is_published' => true,
                'sort' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Drzewo z pielęgnacją',
                'price_range' => '1-3 tys. zł',
                'is_published' => true,
                'sort' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Warsztaty sąsiedzkie',
                'price_range' => '5-20 tys. zł',
                'is_published' => true,
                'sort' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Doświetlenie przejścia',
                'price_range' => '30-90 tys. zł',
                'is_published' => true,
                'sort' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_guide_items');
    }
};

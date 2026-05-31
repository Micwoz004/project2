<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eko_zones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('building_type', 30)->default('mixed');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('eko_zone_address_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_zone_id')->constrained('eko_zones')->cascadeOnDelete();
            $table->string('locality', 150)->nullable();
            $table->string('street', 180)->nullable();
            $table->string('building_from', 20)->nullable();
            $table->string('building_to', 20)->nullable();
            $table->string('exact_building_number', 20)->nullable();
            $table->string('parity', 20)->default('all');
            $table->timestamps();
            $table->index(['client_id', 'locality', 'street']);
        });

        Schema::create('eko_resident_addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eko_zone_id')->nullable()->constrained('eko_zones')->nullOnDelete();
            $table->string('label', 100)->nullable();
            $table->string('building_type', 30)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('commune', 100)->nullable();
            $table->string('locality', 150);
            $table->string('street', 180)->nullable();
            $table->string('building_number', 20);
            $table->string('apartment_number', 20)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('confirmation_status', 30)->default('pending');
            $table->timestamp('confirmation_decided_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'user_id', 'is_active']);
        });

        Schema::create('eko_waste_fractions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 150);
            $table->string('color', 20)->nullable();
            $table->string('icon', 80)->nullable();
            $table->text('description')->nullable();
            $table->longText('what_to_put')->nullable();
            $table->longText('what_not_to_put')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'status']);
        });

        Schema::create('eko_waste_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_waste_fraction_id')->nullable()->constrained('eko_waste_fractions')->nullOnDelete();
            $table->string('name', 150);
            $table->string('normalized_name', 180);
            $table->text('instruction')->nullable();
            $table->boolean('goes_to_pszok')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'normalized_name']);
        });

        Schema::create('eko_waste_item_synonyms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_waste_item_id')->constrained('eko_waste_items')->cascadeOnDelete();
            $table->string('synonym', 150);
            $table->string('normalized_synonym', 180);
            $table->timestamps();
            $table->index(['client_id', 'normalized_synonym']);
        });

        Schema::create('eko_pszok_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 180);
            $table->string('status', 30)->default('active');
            $table->string('phone', 40)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('locality', 150)->nullable();
            $table->string('street', 180)->nullable();
            $table->string('building_number', 20)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('opening_hours')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'status']);
        });

        Schema::create('eko_pszok_fraction', function (Blueprint $table): void {
            $table->foreignId('eko_pszok_point_id')->constrained('eko_pszok_points')->cascadeOnDelete();
            $table->foreignId('eko_waste_fraction_id')->constrained('eko_waste_fractions')->cascadeOnDelete();
            $table->primary(['eko_pszok_point_id', 'eko_waste_fraction_id']);
        });

        Schema::create('eko_collection_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 180);
            $table->string('status', 30)->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'status']);
        });

        Schema::create('eko_collection_schedule_dates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_collection_schedule_id')->constrained('eko_collection_schedules')->cascadeOnDelete();
            $table->foreignId('eko_zone_id')->constrained('eko_zones')->cascadeOnDelete();
            $table->foreignId('eko_waste_fraction_id')->constrained('eko_waste_fractions')->cascadeOnDelete();
            $table->date('collection_date');
            $table->timestamps();
            $table->unique([
                'eko_collection_schedule_id',
                'eko_zone_id',
                'eko_waste_fraction_id',
                'collection_date',
            ], 'eko_schedule_date_unique');
            $table->index(['client_id', 'collection_date']);
        });

        Schema::create('eko_news_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['client_id', 'slug']);
        });

        Schema::create('eko_news_posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_news_category_id')->nullable()->constrained('eko_news_categories')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 180);
            $table->string('scope_type', 30)->default('global');
            $table->foreignId('eko_zone_id')->nullable()->constrained('eko_zones')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('lead', 500)->nullable();
            $table->longText('body');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_id', 'slug']);
            $table->index(['client_id', 'status', 'published_at']);
        });

        Schema::create('eko_air_quality_stations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('external_id', 80)->nullable();
            $table->string('name', 180);
            $table->string('city', 120)->nullable();
            $table->string('street', 180)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['client_id', 'external_id']);
        });

        Schema::create('eko_air_quality_readings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_air_quality_station_id')->constrained('eko_air_quality_stations')->cascadeOnDelete();
            $table->string('parameter_code', 40);
            $table->string('parameter_name', 120);
            $table->decimal('value', 12, 4)->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedTinyInteger('index_value')->nullable();
            $table->string('index_category_name', 80)->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'measured_at']);
        });

        Schema::create('eko_notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 180);
            $table->string('trigger_type', 60);
            $table->string('status', 30)->default('inactive');
            $table->boolean('email_enabled')->default(false);
            $table->string('email_subject_template', 500)->nullable();
            $table->longText('email_body_template')->nullable();
            $table->boolean('sms_enabled')->default(false);
            $table->string('sms_body_template', 1000)->nullable();
            $table->boolean('push_enabled')->default(false);
            $table->string('push_body_template', 1000)->nullable();
            $table->integer('days_before')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'trigger_type', 'status']);
        });

        Schema::create('eko_notification_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->foreignId('eko_notification_template_id')->nullable()->constrained('eko_notification_templates')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('source_key', 180)->nullable();
            $table->string('audience_scope', 120)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'source_key']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eko_notification_events');
        Schema::dropIfExists('eko_notification_templates');
        Schema::dropIfExists('eko_air_quality_readings');
        Schema::dropIfExists('eko_air_quality_stations');
        Schema::dropIfExists('eko_news_posts');
        Schema::dropIfExists('eko_news_categories');
        Schema::dropIfExists('eko_collection_schedule_dates');
        Schema::dropIfExists('eko_collection_schedules');
        Schema::dropIfExists('eko_pszok_fraction');
        Schema::dropIfExists('eko_pszok_points');
        Schema::dropIfExists('eko_waste_item_synonyms');
        Schema::dropIfExists('eko_waste_items');
        Schema::dropIfExists('eko_waste_fractions');
        Schema::dropIfExists('eko_resident_addresses');
        Schema::dropIfExists('eko_zone_address_rules');
        Schema::dropIfExists('eko_zones');
    }
};

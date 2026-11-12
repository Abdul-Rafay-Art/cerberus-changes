<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 160);
            $table->string('email', 254)->index();
            $table->string('phone', 40);
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('registration', 20);
            $table->string('registration_normalized', 20)->index();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin', 50)->nullable();
            $table->string('mileage_display', 80)->nullable();
            $table->timestamps();
            $table->index('customer_id');
        });

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->char('currency', 3)->default('GBP');
            $table->boolean('active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('video_included')->default(false);
            $table->timestamps();
        });

        Schema::create('inspection_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('inspection_sections')->restrictOnDelete();
            $table->string('code', 100)->unique();
            $table->text('prompt');
            $table->text('help_text')->nullable();
            $table->string('response_type', 32)->default('rating');
            $table->string('measurement_label', 120)->nullable();
            $table->string('measurement_unit', 40)->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['section_id', 'sort_order']);
        });

        Schema::create('inspection_features', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('addon_price', 10, 2)->nullable();
            $table->boolean('addon_enabled')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('package_features', function (Blueprint $table): void {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('inspection_features')->cascadeOnDelete();
            $table->boolean('included')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['package_id', 'feature_id']);
        });

        Schema::create('feature_questions', function (Blueprint $table): void {
            $table->foreignId('feature_id')->constrained('inspection_features')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['feature_id', 'question_id']);
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_reference', 32)->unique();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->string('source', 16)->default('public');
            $table->string('status', 24)->default('requested')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('appointment_at')->nullable();
            $table->text('location')->nullable();
            $table->text('staff_notes')->nullable();
            $table->decimal('quoted_total', 10, 2)->default(0);
            $table->char('currency', 3)->default('GBP');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'appointment_at']);
        });

        Schema::create('booking_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->dateTime('preferred_at');
            $table->unsignedTinyInteger('priority');
            $table->timestamps();
            $table->unique(['booking_id', 'priority']);
        });

        Schema::create('booking_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('inspection_features')->restrictOnDelete();
            $table->decimal('price_snapshot', 10, 2);
            $table->timestamps();
            $table->unique(['booking_id', 'feature_id']);
        });

        Schema::create('staff_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->string('display_name', 160);
            $table->string('role', 20)->default('staff');
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('package_snapshot');
            $table->json('feature_snapshot');
            $table->json('rating_scheme_snapshot');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('inspection_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_title_snapshot', 180);
            $table->text('question_text_snapshot');
            $table->unsignedInteger('sort_order');
            $table->string('rating_code', 40)->nullable();
            $table->text('notes')->nullable();
            $table->text('measurements')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();
            $table->unique(['inspection_id', 'sort_order']);
            $table->index(['inspection_id', 'question_id']);
        });

        Schema::create('inspection_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('storage_key', 512)->unique();
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('byte_size');
            $table->string('caption', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->index(['inspection_id', 'sort_order']);
        });

        Schema::create('inspection_videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('youtube');
            $table->text('source_url');
            $table->string('video_id', 20);
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('storage_key', 512)->unique();
            $table->unsignedInteger('version');
            $table->timestamp('generated_at');
            $table->uuid('generated_by')->nullable();
            $table->timestamps();
            $table->unique(['inspection_id', 'version']);
        });

        Schema::create('report_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->index(['report_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_access_tokens');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('inspection_videos');
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('inspection_checks');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('staff_users');
        Schema::dropIfExists('booking_addons');
        Schema::dropIfExists('booking_preferences');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('feature_questions');
        Schema::dropIfExists('package_features');
        Schema::dropIfExists('inspection_features');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('inspection_sections');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};

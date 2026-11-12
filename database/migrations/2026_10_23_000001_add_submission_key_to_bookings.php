<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->uuid('submission_key')->nullable()->after('booking_reference');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unique('submission_key');
        });

    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique(['submission_key']);
            $table->dropColumn('submission_key');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_checks', function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(0)->after('last_saved_at');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_checks', function (Blueprint $table): void {
            $table->dropColumn('version');
        });
    }
};

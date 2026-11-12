<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_options', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('label', 100);
            $table->char('color', 7);
            $table->unsignedSmallInteger('score')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_options');
    }
};

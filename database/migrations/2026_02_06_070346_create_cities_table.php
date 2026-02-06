<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('city_slug');
            $table->string('city_name');
            $table->string('region_code', 3);
            $table->string('region_name');
            $table->string('country_code', 2);
            $table->string('country_name');
            $table->unsignedInteger('article_count')->default(0);
            $table->timestamps();

            $table->unique(['country_code', 'region_code', 'city_slug']);
            $table->index(['country_code', 'region_code']);
            $table->index('city_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};

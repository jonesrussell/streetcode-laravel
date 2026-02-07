<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'slug')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('title');
            });
        }

        if (! Schema::hasColumn('articles', 'status')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('status')->default('published')->index()->after('author');
            });
        }

        // Backfill slugs from titles
        DB::table('articles')->whereNull('slug')->orderBy('id')->each(function ($article) {
            $slug = Str::limit(Str::slug($article->title), 255, '');
            $original = $slug;
            $counter = 1;

            while (DB::table('articles')->where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                $suffix = "-{$counter}";
                $slug = Str::limit($original, 255 - strlen($suffix), '').$suffix;
                $counter++;
            }

            DB::table('articles')->where('id', $article->id)->update(['slug' => $slug]);
        });

        // Make slug NOT NULL after backfill
        Schema::table('articles', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['slug', 'status']);
        });
    }
};

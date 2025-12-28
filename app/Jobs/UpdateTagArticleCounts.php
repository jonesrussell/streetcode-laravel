<?php

namespace App\Jobs;

use App\Models\Tag;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateTagArticleCounts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Tag::query()->each(function (Tag $tag) {
            $tag->update([
                'article_count' => $tag->articles()->count(),
            ]);
        });
    }
}

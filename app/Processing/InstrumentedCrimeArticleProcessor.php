<?php

namespace App\Processing;

use App\Services\IngestionMetricsService;
use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleModel;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;

class InstrumentedCrimeArticleProcessor implements ArticleProcessor
{
    public function __construct(
        protected CrimeArticleProcessor $inner,
        protected IngestionMetricsService $metrics,
    ) {}

    public function shouldProcess(array $data): bool
    {
        return $this->inner->shouldProcess($data);
    }

    public function process(array $data, ?ArticleModel $article): ?Model
    {
        $this->metrics->incrementReceived();

        $result = $this->inner->process($data, $article);

        if ($result === null) {
            $this->metrics->incrementSkipped();
        } else {
            $this->metrics->incrementIngested();
        }

        return $result;
    }
}

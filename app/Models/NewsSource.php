<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use JonesRussell\NorthCloud\Models\NewsSource as BaseNewsSource;

class NewsSource extends BaseNewsSource
{
    protected function biasColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->bias_rating) {
                'left' => 'blue',
                'center-left' => 'sky',
                'center' => 'gray',
                'center-right' => 'orange',
                'right' => 'red',
                default => 'gray',
            },
        );
    }

    protected static function newFactory()
    {
        return \Database\Factories\NewsSourceFactory::new();
    }
}

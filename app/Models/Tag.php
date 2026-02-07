<?php

namespace App\Models;

use JonesRussell\NorthCloud\Models\Tag as BaseTag;

class Tag extends BaseTag
{
    protected static function newFactory()
    {
        return \Database\Factories\TagFactory::new();
    }
}

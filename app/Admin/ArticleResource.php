<?php

namespace App\Admin;

use JonesRussell\NorthCloud\Admin\ArticleResource as BaseArticleResource;

class ArticleResource extends BaseArticleResource
{
    public function tableColumns(): array
    {
        $columns = parent::tableColumns();

        // Insert author column after title
        $index = collect($columns)->search(fn ($col) => $col['name'] === 'title');
        if ($index !== false) {
            array_splice($columns, $index + 1, 0, [
                ['name' => 'author', 'label' => 'Author'],
            ]);
        }

        return $columns;
    }
}

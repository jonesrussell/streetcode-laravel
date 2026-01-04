<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { Article, PaginatedArticles } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ArticleStatusBadge from './ArticleStatusBadge.vue';
import { Edit, Trash2, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { index as articlesIndex, edit as articlesEdit } from '@/routes/dashboard/articles';

interface Props {
    articles: PaginatedArticles;
    filters?: {
        sort?: string;
        direction?: 'asc' | 'desc';
        [key: string]: any;
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    delete: [article: Article];
}>();

const toggleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        articlesIndex().url,
        {
            ...props.filters,
            sort: column,
            direction: newDirection,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const getSortIcon = (column: string) => {
    if (props.filters?.sort !== column) return null;
    return props.filters?.direction === 'asc' ? ArrowUp : ArrowDown;
};

const handleEdit = (article: Article) => {
    router.get(articlesEdit(article.id).url);
};

const handleDelete = (article: Article) => {
    emit('delete', article);
};

const formatDate = (date: string | null) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="rounded-md border">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-muted"
                            @click="toggleSort('id')"
                        >
                            <div class="flex items-center gap-1">
                                ID
                                <component
                                    :is="getSortIcon('id')"
                                    v-if="getSortIcon('id')"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th
                            class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-muted"
                            @click="toggleSort('title')"
                        >
                            <div class="flex items-center gap-1">
                                Title
                                <component
                                    :is="getSortIcon('title')"
                                    v-if="getSortIcon('title')"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Source
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Tags
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-medium">
                            Status
                        </th>
                        <th
                            class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-muted"
                            @click="toggleSort('published_at')"
                        >
                            <div class="flex items-center gap-1">
                                Published
                                <component
                                    :is="getSortIcon('published_at')"
                                    v-if="getSortIcon('published_at')"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th
                            class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-muted"
                            @click="toggleSort('view_count')"
                        >
                            <div class="flex items-center gap-1">
                                Views
                                <component
                                    :is="getSortIcon('view_count')"
                                    v-if="getSortIcon('view_count')"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="article in articles?.data ?? []"
                        :key="article.id"
                        class="border-b hover:bg-muted/50 transition-colors"
                    >
                        <td class="px-4 py-3 text-sm">
                            {{ article.id }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-md">
                                <div class="font-medium text-sm line-clamp-2">
                                    {{ article.title }}
                                </div>
                                <div
                                    v-if="article.author"
                                    class="text-xs text-muted-foreground mt-1"
                                >
                                    by {{ article.author }}
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                v-if="article.news_source"
                                variant="outline"
                                class="text-xs"
                            >
                                {{ article.news_source.name }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                <Badge
                                    v-for="tag in article.tags?.slice(0, 3)"
                                    :key="tag.id"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    {{ tag.name }}
                                </Badge>
                                <Badge
                                    v-if="article.tags && article.tags.length > 3"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    +{{ article.tags.length - 3 }}
                                </Badge>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <ArticleStatusBadge
                                :published-at="article.published_at"
                            />
                        </td>
                        <td class="px-4 py-3 text-sm text-muted-foreground">
                            {{ formatDate(article.published_at) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-muted-foreground">
                            {{ article.view_count.toLocaleString() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="handleEdit(article)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="handleDelete(article)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!articles?.data || articles.data.length === 0">
                        <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm">No articles found.</p>
                                <p class="text-xs">Try adjusting your filters or create a new article.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Article, PaginatedArticles } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import ArticleStatusBadge from './ArticleStatusBadge.vue';
import { Edit, Trash2, ArrowUp, ArrowDown, Eye, EyeOff } from 'lucide-vue-next';
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
    'update:selected': [ids: number[]];
    'toggle-publish': [article: Article];
}>();

const selectedIds = ref<number[]>([]);

const allArticleIds = computed(() => props.articles?.data?.map(a => a.id) ?? []);

const isAllSelected = computed(() => {
    if (allArticleIds.value.length === 0) return false;
    return allArticleIds.value.every(id => selectedIds.value.includes(id));
});

const isSomeSelected = computed(() => {
    return selectedIds.value.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = selectedIds.value.filter(id => !allArticleIds.value.includes(id));
    } else {
        const newIds = allArticleIds.value.filter(id => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
    emit('update:selected', selectedIds.value);
};

const toggleSelect = (articleId: number) => {
    if (selectedIds.value.includes(articleId)) {
        selectedIds.value = selectedIds.value.filter(id => id !== articleId);
    } else {
        selectedIds.value = [...selectedIds.value, articleId];
    }
    emit('update:selected', selectedIds.value);
};

const isSelected = (articleId: number) => {
    return selectedIds.value.includes(articleId);
};

// Clear selections when articles data actually changes (pagination, filtering, etc.)
watch(() => props.articles?.data?.map(a => a.id).join(','), () => {
    selectedIds.value = [];
    emit('update:selected', []);
});

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

const handleTogglePublish = (article: Article) => {
    emit('toggle-publish', article);
};

const formatDate = (date: string | null) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isPublished = (article: Article) => {
    return article.published_at !== null;
};
</script>

<template>
    <div class="rounded-md border">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium w-12">
                            <Checkbox
                                :checked="isAllSelected"
                                :indeterminate="isSomeSelected"
                                @update:checked="toggleSelectAll"
                            />
                        </th>
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
                        <td class="px-4 py-3">
                            <Checkbox
                                :checked="isSelected(article.id)"
                                @update:checked="() => toggleSelect(article.id)"
                            />
                        </td>
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
                                    :title="isPublished(article) ? 'Unpublish' : 'Publish'"
                                    @click="handleTogglePublish(article)"
                                >
                                    <component
                                        :is="isPublished(article) ? EyeOff : Eye"
                                        class="h-4 w-4"
                                    />
                                </Button>
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
                        <td colspan="9" class="px-4 py-12 text-center text-muted-foreground">
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

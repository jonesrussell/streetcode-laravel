<script setup lang="ts">
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as articlesIndex } from '@/routes/dashboard/articles';
import type { Article, BreadcrumbItem, PaginatedArticles } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowUpDown,
    FileText,
    RotateCcw,
    Trash2,
    Archive,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface NewsSource {
    id: number;
    name: string;
}

interface Props {
    articles: PaginatedArticles;
    filters: {
        search?: string;
        source?: number;
        channel?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    channels: string[];
    newsSources: NewsSource[];
    stats: {
        trashed: number;
        active: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Articles', href: articlesIndex().url },
    { title: 'Trashed', href: '/dashboard/articles/trashed' },
];

const searchQuery = ref(props.filters.search || '');
const sourceFilter = ref<string>(props.filters.source?.toString() || 'all');
const channelFilter = ref<string>(props.filters.channel || 'all');
const selectedIds = ref<number[]>([]);
const isRestoring = ref(false);
const isForceDeleting = ref(false);
const deleteDialogOpen = ref(false);
const articleToDelete = ref<Article | null>(null);
const isBulkAction = ref(false);

const applyFilters = () => {
    router.get(
        '/dashboard/articles/trashed',
        {
            ...props.filters,
            search: searchQuery.value || undefined,
            source: sourceFilter.value !== 'all' ? sourceFilter.value : undefined,
            channel: channelFilter.value !== 'all' ? channelFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const handleSearch = () => {
    applyFilters();
};

const handleSourceChange = (value: unknown) => {
    sourceFilter.value = value != null && (typeof value === 'string' || typeof value === 'number')
        ? String(value)
        : 'all';
    applyFilters();
};

const handleChannelChange = (value: unknown) => {
    channelFilter.value = value != null && typeof value === 'string'
        ? value
        : 'all';
    applyFilters();
};

const toggleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        '/dashboard/articles/trashed',
        { ...props.filters, sort: column, direction: newDirection },
        { preserveState: true, preserveScroll: true },
    );
};

const handleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedIds.value = props.articles.data.map((a) => a.id);
    } else {
        selectedIds.value = [];
    }
};

const handleSelectOne = (id: number, checked: boolean) => {
    if (checked) {
        selectedIds.value = [...selectedIds.value, id];
    } else {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    }
};

const handleRestore = (article: Article) => {
    isRestoring.value = true;
    router.post(
        `/dashboard/articles/${article.id}/restore`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRestoring.value = false;
            },
        },
    );
};

const handleBulkRestore = () => {
    if (selectedIds.value.length === 0) return;

    isRestoring.value = true;
    router.post(
        '/dashboard/articles/bulk-restore',
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isRestoring.value = false;
            },
        },
    );
};

const handleForceDeleteClick = (article: Article) => {
    articleToDelete.value = article;
    isBulkAction.value = false;
    deleteDialogOpen.value = true;
};

const handleBulkForceDelete = () => {
    if (selectedIds.value.length === 0) return;
    articleToDelete.value = null;
    isBulkAction.value = true;
    deleteDialogOpen.value = true;
};

const confirmForceDelete = () => {
    if (isBulkAction.value) {
        isForceDeleting.value = true;
        router.post(
            '/dashboard/articles/bulk-force-delete',
            { ids: selectedIds.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteDialogOpen.value = false;
                    selectedIds.value = [];
                },
                onFinish: () => {
                    isForceDeleting.value = false;
                },
            },
        );
    } else if (articleToDelete.value) {
        isForceDeleting.value = true;
        router.delete(
            `/dashboard/articles/${articleToDelete.value.id}/force-delete`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteDialogOpen.value = false;
                    articleToDelete.value = null;
                },
                onFinish: () => {
                    isForceDeleting.value = false;
                },
            },
        );
    }
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    articleToDelete.value = null;
    isBulkAction.value = false;
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url);
};

const getPageNumbers = () => {
    if (!props.articles?.last_page) return [];
    const current = props.articles.current_page;
    const last = props.articles.last_page;
    const pages: (number | string)[] = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 3) {
            for (let i = 1; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(last);
        } else if (current >= last - 2) {
            pages.push(1);
            pages.push('...');
            for (let i = last - 4; i <= last; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(last);
        }
    }

    return pages;
};

const goToPageNumber = (page: number | string) => {
    if (typeof page === 'string' || page === props.articles?.current_page)
        return;

    router.get(
        '/dashboard/articles/trashed',
        {
            ...props.filters,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const allSelected = computed(
    () =>
        props.articles.data.length > 0 &&
        selectedIds.value.length === props.articles.data.length,
);
const showPagination = computed(() => {
    const lastPage = props.articles?.last_page;
    return lastPage ? lastPage > 1 : false;
});

const deleteDescription = computed(() => {
    if (isBulkAction.value) {
        const count = selectedIds.value.length;
        return count === 1
            ? 'Are you sure you want to PERMANENTLY delete this article? This action cannot be undone.'
            : `Are you sure you want to PERMANENTLY delete ${count} articles? This action cannot be undone.`;
    }
    return articleToDelete.value
        ? `Are you sure you want to PERMANENTLY delete "${articleToDelete.value.title}"? This action cannot be undone.`
        : '';
});

const getChannel = (article: Article): string => {
    const metadata = article.metadata as { publisher?: { channel?: string } } | null;
    return metadata?.publisher?.channel || 'unknown';
};

const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-CA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Clear selections when articles data changes
watch(
    () => props.articles?.data?.map((a) => a.id).join(','),
    () => {
        selectedIds.value = [];
    },
);
</script>

<template>
    <Head title="Trashed Articles - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Trashed Articles</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage soft-deleted articles - restore or permanently delete
                    </p>
                </div>
                <Button variant="outline" as="a" :href="articlesIndex().url">
                    <FileText class="mr-2 h-4 w-4" />
                    Back to Articles
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Trashed Articles
                        </CardTitle>
                        <Archive class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats?.trashed ?? 0 }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Active Articles
                        </CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats?.active ?? 0 }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by title..."
                                @keyup.enter="handleSearch"
                                class="max-w-sm"
                            />
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <Select
                                v-model="sourceFilter"
                                @update:model-value="handleSourceChange"
                            >
                                <SelectTrigger class="w-[180px]">
                                    <SelectValue placeholder="All Sources" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Sources</SelectItem>
                                    <SelectItem
                                        v-for="source in newsSources"
                                        :key="source.id"
                                        :value="source.id.toString()"
                                    >
                                        {{ source.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select
                                v-model="channelFilter"
                                @update:model-value="handleChannelChange"
                            >
                                <SelectTrigger class="w-[200px]">
                                    <SelectValue placeholder="All Channels" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Channels</SelectItem>
                                    <SelectItem
                                        v-for="channel in channels"
                                        :key="channel"
                                        :value="channel"
                                    >
                                        {{ channel }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline" @click="handleSearch">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions Toolbar -->
            <Card v-if="hasSelected" class="border-primary/50 bg-primary/5">
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">
                            {{ selectedIds.length }} article{{
                                selectedIds.length === 1 ? '' : 's'
                            }}
                            selected
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isRestoring || isForceDeleting"
                                @click="handleBulkRestore"
                            >
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Restore
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="isRestoring || isForceDeleting"
                                @click="handleBulkForceDelete"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete Permanently
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Articles Table -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="w-[50px] p-4 text-left">
                                    <Checkbox
                                        :checked="allSelected"
                                        @update:checked="handleSelectAll"
                                    />
                                </th>
                                <th
                                    class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50"
                                    @click="toggleSort('id')"
                                >
                                    <div class="flex items-center gap-1">
                                        ID
                                        <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th
                                    class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50"
                                    @click="toggleSort('title')"
                                >
                                    <div class="flex items-center gap-1">
                                        Title
                                        <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th class="p-4 text-left font-medium">Source</th>
                                <th class="p-4 text-left font-medium">Channel</th>
                                <th
                                    class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50"
                                    @click="toggleSort('deleted_at')"
                                >
                                    <div class="flex items-center gap-1">
                                        Deleted At
                                        <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th class="p-4 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="article in articles.data"
                                :key="article.id"
                                class="border-b hover:bg-muted/50"
                            >
                                <td class="p-4">
                                    <Checkbox
                                        :checked="selectedIds.includes(article.id)"
                                        @update:checked="(checked: boolean) => handleSelectOne(article.id, checked)"
                                    />
                                </td>
                                <td class="p-4 font-mono text-xs">
                                    {{ article.id }}
                                </td>
                                <td class="p-4 max-w-[300px]">
                                    <div class="truncate font-medium">
                                        {{ article.title }}
                                    </div>
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ article.news_source?.name || '-' }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    <code class="text-xs bg-muted px-1 py-0.5 rounded">
                                        {{ getChannel(article) }}
                                    </code>
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ formatDate(article.deleted_at) }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :disabled="isRestoring"
                                            @click="handleRestore(article)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="isForceDeleting"
                                            @click="handleForceDeleteClick(article)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="articles.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-muted-foreground">
                                    No trashed articles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Pagination -->
            <div
                v-if="showPagination"
                class="flex items-center justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    Showing {{ articles.from }} to {{ articles.to }} of
                    {{ articles.total }} results
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!articles?.prev_page_url"
                        @click="goToPage(articles.prev_page_url)"
                    >
                        Previous
                    </Button>
                    <div class="flex gap-1">
                        <Button
                            v-for="page in getPageNumbers()"
                            :key="page"
                            size="sm"
                            :variant="
                                page === articles.current_page
                                    ? 'default'
                                    : 'outline'
                            "
                            :disabled="typeof page === 'string'"
                            @click="goToPageNumber(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!articles?.next_page_url"
                        @click="goToPage(articles.next_page_url)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Permanently Delete"
            :description="deleteDescription"
            :loading="isForceDeleting"
            @confirm="confirmForceDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

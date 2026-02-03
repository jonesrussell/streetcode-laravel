<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import type { Article, PaginatedArticles, BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import ArticlesTable from '@/components/admin/ArticlesTable.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import { Plus, FileText, FilePlus, FileCheck, Trash2, Eye, EyeOff } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import {
    index as articlesIndex,
    create as articlesCreate,
    destroy as articlesDestroy,
    bulkDelete as articlesBulkDelete,
    bulkPublish as articlesBulkPublish,
    bulkUnpublish as articlesBulkUnpublish,
    togglePublish as articlesTogglePublish,
} from '@/routes/dashboard/articles';

interface Props {
    articles: PaginatedArticles;
    filters: {
        status?: 'draft' | 'published';
        search?: string;
        tag?: string;
        source?: number;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    stats: {
        total: number;
        drafts: number;
        published: number;
    };
}

const props = defineProps<Props>();


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Articles', href: articlesIndex().url },
];

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref<string>(props.filters.status || 'all');
const deleteDialogOpen = ref(false);
const articleToDelete = ref<Article | null>(null);
const isDeleting = ref(false);
const selectedIds = ref<number[]>([]);
const isBulkDeleting = ref(false);
const isBulkPublishing = ref(false);
const isBulkUnpublishing = ref(false);
const isTogglingPublish = ref(false);

const applyFilters = () => {
    router.get(
        articlesIndex().url,
        {
            ...props.filters,
            search: searchQuery.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handleSearch = () => {
    applyFilters();
};

const handleStatusChange = (value: string) => {
    statusFilter.value = value;
    applyFilters();
};

const handleCreateArticle = () => {
    router.get(articlesCreate().url);
};

const handleDeleteClick = (article: Article) => {
    articleToDelete.value = article;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!articleToDelete.value) return;

    isDeleting.value = true;

    router.delete(articlesDestroy(articleToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            articleToDelete.value = null;
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    articleToDelete.value = null;
    if (!articleToDelete.value) {
        selectedIds.value = [];
    }
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url);
};

const handleSelectedUpdate = (ids: number[]) => {
    selectedIds.value = ids;
};

const handleBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    deleteDialogOpen.value = true;
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;

    isBulkDeleting.value = true;

    router.post(
        articlesBulkDelete().url,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkDeleting.value = false;
            },
        }
    );
};

const handleBulkPublish = () => {
    if (selectedIds.value.length === 0) return;

    isBulkPublishing.value = true;

    router.post(
        articlesBulkPublish().url,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkPublishing.value = false;
            },
        }
    );
};

const handleBulkUnpublish = () => {
    if (selectedIds.value.length === 0) return;

    isBulkUnpublishing.value = true;

    router.post(
        articlesBulkUnpublish().url,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkUnpublishing.value = false;
            },
        }
    );
};

const handleTogglePublish = (article: Article) => {
    isTogglingPublish.value = true;

    router.post(
        articlesTogglePublish(article.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isTogglingPublish.value = false;
            },
        }
    );
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
    if (typeof page === 'string' || page === props.articles?.current_page) return;

    router.get(
        articlesIndex().url,
        {
            ...props.filters,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const bulkDeleteDescription = computed(() => {
    const count = selectedIds.value.length;
    return count === 1
        ? 'Are you sure you want to delete this article? This action cannot be undone.'
        : `Are you sure you want to delete ${count} articles? This action cannot be undone.`;
});

const showPagination = computed(() => {
    const lastPage = props.articles?.last_page;
    const shouldShow = lastPage ? lastPage > 1 : false;
    return shouldShow;
});

// Clear selections when articles data actually changes (pagination, filtering, etc.)
watch(() => props.articles?.data?.map(a => a.id).join(','), () => {
    selectedIds.value = [];
});

// Clear selections when articles change (pagination, filtering, etc.)
watch(() => props.articles?.data, () => {
    selectedIds.value = [];
}, { deep: true });
</script>

<template>
    <Head title="Articles - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Articles</h1>
                    <p class="text-muted-foreground mt-1">
                        Manage your article content
                    </p>
                </div>
                <Button @click="handleCreateArticle">
                    <Plus class="h-4 w-4 mr-2" />
                    Create Article
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">
                            Total Articles
                        </CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.total ?? 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">
                            Drafts
                        </CardTitle>
                        <FilePlus class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.drafts ?? 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">
                            Published
                        </CardTitle>
                        <FileCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.published ?? 0 }}</div>
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
                                placeholder="Search articles..."
                                @keyup.enter="handleSearch"
                                class="max-w-sm"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Select v-model="statusFilter" @update:model-value="handleStatusChange">
                                <SelectTrigger class="w-[150px]">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="draft">Drafts</SelectItem>
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
                            {{ selectedIds.length }} article{{ selectedIds.length === 1 ? '' : 's' }} selected
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isBulkPublishing || isBulkUnpublishing || isBulkDeleting"
                                @click="handleBulkPublish"
                            >
                                <Eye class="h-4 w-4 mr-2" />
                                Publish
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isBulkPublishing || isBulkUnpublishing || isBulkDeleting"
                                @click="handleBulkUnpublish"
                            >
                                <EyeOff class="h-4 w-4 mr-2" />
                                Unpublish
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="isBulkPublishing || isBulkUnpublishing || isBulkDeleting"
                                @click="handleBulkDelete"
                            >
                                <Trash2 class="h-4 w-4 mr-2" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Articles Table -->
            <ArticlesTable
                v-if="articles"
                :articles="articles"
                :filters="filters"
                :selectedIds="selectedIds"
                @delete="handleDeleteClick"
                @update:selected="handleSelectedUpdate"
                @toggle-publish="handleTogglePublish"
            />

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
                            :variant="page === articles.current_page ? 'default' : 'outline'"
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
            :title="articleToDelete ? 'Delete Article' : 'Delete Articles'"
            :description="articleToDelete ? `Are you sure you want to delete &quot;${articleToDelete.title}&quot;? This action cannot be undone.` : bulkDeleteDescription"
            :loading="isDeleting || isBulkDeleting"
            @confirm="articleToDelete ? confirmDelete() : confirmBulkDelete()"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

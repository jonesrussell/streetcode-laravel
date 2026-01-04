<script setup lang="ts">
import { ref } from 'vue';
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
import { Plus, FileText, FilePlus, FileCheck } from 'lucide-vue-next';
import { destroy } from '@/actions/App/Http/Controllers/Admin/ArticleController';
import { dashboard } from '@/routes';
import { index as articlesIndex, create as articlesCreate, destroy as articlesDestroy } from '@/routes/dashboard/articles';

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
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    articleToDelete.value = null;
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url);
};
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

            <!-- Articles Table -->
            <ArticlesTable
                v-if="articles"
                :articles="articles"
                :filters="filters"
                @delete="handleDeleteClick"
            />

            <!-- Pagination -->
            <div
                v-if="articles?.meta?.last_page && articles.meta.last_page > 1"
                class="flex items-center justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    Showing {{ articles.meta.from }} to {{ articles.meta.to }} of
                    {{ articles.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!articles?.links?.prev"
                        @click="goToPage(articles.links.prev)"
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!articles?.links?.next"
                        @click="goToPage(articles.links.next)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete Article"
            :description="`Are you sure you want to delete &quot;${articleToDelete?.title}&quot;? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

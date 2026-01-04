<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Article, type Tag, type PaginatedArticles } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import ArticleCard from '@/components/ArticleCard.vue';
import TagFilter from '@/components/TagFilter.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, FileCheck, FilePlus, Star, TrendingUp, Eye } from 'lucide-vue-next';

interface Props {
    articles: PaginatedArticles;
    featuredArticles: Article[];
    popularTags: Tag[];
    stats?: {
        total: number;
        published: number;
        drafts: number;
        featured: number;
        recent: number;
        total_views: number;
    };
    filters?: {
        tag?: string;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Statistics Cards -->
            <section v-if="stats" class="mb-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle class="text-sm font-medium">
                                Total Articles
                            </CardTitle>
                            <FileText class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total }}</div>
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
                            <div class="text-2xl font-bold">{{ stats.published }}</div>
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
                            <div class="text-2xl font-bold">{{ stats.drafts }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle class="text-sm font-medium">
                                Featured
                            </CardTitle>
                            <Star class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.featured }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle class="text-sm font-medium">
                                Recent (7d)
                            </CardTitle>
                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.recent }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle class="text-sm font-medium">
                                Total Views
                            </CardTitle>
                            <Eye class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_views.toLocaleString() }}</div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- Featured Articles -->
            <section v-if="featuredArticles.length" class="mb-4">
                <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
                    Featured Stories
                </h2>
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <ArticleCard
                        v-for="article in featuredArticles"
                        :key="article.id"
                        :article="article"
                    />
                </div>
            </section>

            <!-- Tag Filters -->
            <section v-if="popularTags.length" class="mb-4">
                <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Filter by Category
                </h3>
                <TagFilter :tags="popularTags" :active-tag="filters?.tag" :route="dashboard().url" />
            </section>

            <!-- Article Grid -->
            <section>
                <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
                    Latest Articles
                </h2>
                <div v-if="articles?.data?.length" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ArticleCard
                        v-for="article in articles.data"
                        :key="article.id"
                        :article="article"
                    />
                </div>

                <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
                    No articles found.
                </div>

                <!-- Pagination -->
                <div
                    v-if="articles?.meta?.last_page && articles.meta.last_page > 1"
                    class="mt-8 flex justify-center gap-2"
                >
                    <Button
                        v-for="page in articles.meta.last_page"
                        :key="page"
                        :variant="page === articles.meta.current_page ? 'default' : 'outline'"
                        size="sm"
                        @click="router.get(dashboard().url, { ...filters, page })"
                    >
                        {{ page }}
                    </Button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

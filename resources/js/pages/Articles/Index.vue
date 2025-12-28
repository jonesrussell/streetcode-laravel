<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import type { Article, Tag, PaginatedArticles } from '@/types'
import ArticleCard from '@/components/ArticleCard.vue'
import TagFilter from '@/components/TagFilter.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'

interface Props {
    articles: PaginatedArticles
    featuredArticles: Article[]
    popularTags: Tag[]
    filters: {
        tag?: string
        search?: string
        source?: number
    }
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')

const performSearch = () => {
    router.get('/', { search: searchQuery.value }, { preserveState: true })
}
</script>

<template>
    <Head title="Canadian Crime News - Streetcode.net" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <header class="border-b bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Streetcode.net</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Canadian Crime News Aggregation
                </p>

                <!-- Search Bar -->
                <div class="mt-4 flex gap-2">
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search articles..."
                        class="max-w-md"
                        @keyup.enter="performSearch"
                    />
                    <Button @click="performSearch">Search</Button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Featured Articles -->
            <section v-if="featuredArticles.length" class="mb-12">
                <h2 class="mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                    Featured Stories
                </h2>
                <div class="grid gap-6 md:grid-cols-3">
                    <ArticleCard
                        v-for="article in featuredArticles"
                        :key="article.id"
                        :article="article"
                    />
                </div>
            </section>

            <!-- Tag Filters -->
            <section class="mb-8">
                <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Filter by Category
                </h3>
                <TagFilter :tags="popularTags" :active-tag="filters.tag" />
            </section>

            <!-- Article Grid -->
            <section>
                <div v-if="articles.data.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <ArticleCard
                        v-for="article in articles.data"
                        :key="article.id"
                        :article="article"
                    />
                </div>

                <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
                    No articles found. Try adjusting your filters.
                </div>

                <!-- Pagination -->
                <div
                    v-if="articles.meta.last_page > 1"
                    class="mt-8 flex justify-center gap-2"
                >
                    <Button
                        v-for="page in articles.meta.last_page"
                        :key="page"
                        :variant="page === articles.meta.current_page ? 'default' : 'outline'"
                        size="sm"
                        @click="router.get('/', { ...filters, page })"
                    >
                        {{ page }}
                    </Button>
                </div>
            </section>
        </main>
    </div>
</template>

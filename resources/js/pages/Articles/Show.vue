<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import type { Article } from '@/types'
import { Badge } from '@/components/ui/badge'
import { Calendar, User, ExternalLink } from 'lucide-vue-next'
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue'
import ArticleCard from '@/components/ArticleCard.vue'

interface Props {
    article: Article
    relatedArticles: Article[]
}

const props = defineProps<Props>()

const formattedDate = props.article.published_at
    ? new Date(props.article.published_at).toLocaleDateString('en-CA', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
      })
    : 'Not published'

const description = props.article.excerpt || props.article.metadata?.og_description || ''
</script>

<template>
    <Head :title="article.title" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <header class="border-b bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    ← Back to Home
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Article Header -->
            <article>
                <header class="mb-8">
                    <h1 class="mb-4 text-4xl font-bold text-gray-900 dark:text-white">
                        {{ article.title }}
                    </h1>

                    <!-- Meta Info -->
                    <div
                        class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400"
                    >
                        <div class="flex items-center gap-2">
                            <Calendar class="size-4" />
                            {{ formattedDate }}
                        </div>

                        <div v-if="article.author" class="flex items-center gap-2">
                            <User class="size-4" />
                            {{ article.author }}
                        </div>

                        <SourceCredibilityBadge v-if="article.news_source" :source="article.news_source" />
                    </div>

                    <!-- Tags -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Badge
                            v-for="tag in article.tags"
                            :key="tag.id"
                            :variant="tag.type === 'crime_category' ? 'default' : 'secondary'"
                        >
                            {{ tag.name }}
                        </Badge>
                    </div>
                </header>

                <!-- Featured Image -->
                <img
                    v-if="article.image_url"
                    :src="article.image_url"
                    :alt="article.title"
                    class="mb-8 w-full rounded-lg object-cover"
                />

                <!-- Description -->
                <p v-if="description" class="text-lg text-gray-600 dark:text-gray-400">
                    {{ description }}
                </p>

                <!-- Read Full Article Link -->
                <a
                    :href="article.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-6 inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 dark:text-blue-400"
                >
                    Read full article at {{ article.news_source?.name ?? 'source' }}
                    <ExternalLink class="size-4" />
                </a>
            </article>

            <!-- Related Articles -->
            <section v-if="relatedArticles.length" class="mt-16">
                <h2 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">
                    Related Articles
                </h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <ArticleCard
                        v-for="relatedArticle in relatedArticles"
                        :key="relatedArticle.id"
                        :article="relatedArticle"
                    />
                </div>
            </section>
        </main>
    </div>
</template>

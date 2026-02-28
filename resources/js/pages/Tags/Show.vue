<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/TagController';
import ArticleCard from '@/components/ArticleCard.vue';
import ArticlePagination from '@/components/ArticlePagination.vue';
import { Badge } from '@/components/ui/badge';
import { getTagBadgeColor } from '@/composables/useTagColors';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PaginatedArticles, Tag } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Tag as TagIcon } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

interface Props {
    tag: Tag;
    articles: PaginatedArticles;
}

const props = defineProps<Props>();

const currentPath = show.url(props.tag);
</script>

<template>
    <Head :title="`${tag.name} News | Streetcode.net`">
        <meta
            name="description"
            head-key="description"
            :content="`${tag.name} crime news and stories. Browse ${tag.article_count} articles.`"
        />
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="`${tag.name} News`" />
        <meta
            property="og:description"
            :content="`${tag.name} crime news and stories. Browse ${tag.article_count} articles.`"
        />
        <meta property="og:image" content="/logo.png" />
    </Head>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Back Link -->
        <Link
            href="/"
            class="mb-6 inline-flex items-center text-sm text-public-text-secondary hover:text-public-accent"
        >
            <ChevronLeft class="mr-1 size-4" />
            Back to Home
        </Link>

        <!-- Tag Header -->
        <header class="mb-8">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-12 items-center justify-center rounded-lg bg-public-accent/10"
                >
                    <TagIcon class="size-6 text-public-accent" />
                </div>
                <div>
                    <h1
                        class="font-heading text-3xl font-bold text-public-text"
                    >
                        {{ tag.name }}
                    </h1>
                    <p class="text-public-text-muted">
                        {{ tag.article_count }} articles
                    </p>
                </div>
            </div>
            <p v-if="tag.description" class="mt-4 text-public-text-secondary">
                {{ tag.description }}
            </p>
        </header>

        <!-- Articles Grid -->
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-heading text-lg font-bold text-public-text">
                    Latest {{ tag.name }} News
                </h2>
                <Badge :class="getTagBadgeColor(tag.color)">
                    {{ articles?.meta?.total ?? articles.data.length }} articles
                </Badge>
            </div>

            <div
                v-if="articles?.data?.length"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <ArticleCard
                    v-for="article in articles.data"
                    :key="article.id"
                    :article="article"
                />
            </div>

            <div
                v-else
                class="rounded-lg bg-public-bg-subtle py-12 text-center text-public-text-muted"
            >
                No articles found for this category.
            </div>

            <ArticlePagination
                v-if="articles?.meta?.last_page && articles.meta.last_page > 1"
                :current-page="articles.meta.current_page"
                :last-page="articles.meta.last_page"
                :route="currentPath"
                :filters="{}"
            />
        </section>
    </main>
</template>

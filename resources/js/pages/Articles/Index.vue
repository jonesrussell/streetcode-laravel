<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticlePagination from '@/components/ArticlePagination.vue';
import HeroBriefing from '@/components/HeroBriefing.vue';
import NewsletterSignup from '@/components/NewsletterSignup.vue';
import TagFilter from '@/components/TagFilter.vue';
import TopStoriesList from '@/components/TopStoriesList.vue';
import TopicSection from '@/components/TopicSection.vue';
import TrendingTopics from '@/components/TrendingTopics.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type {
    Article,
    CategoryArticles,
    PaginatedArticles,
    Tag,
} from '@/types';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: PublicLayout });

interface Props {
    heroArticle: Article | null;
    featuredArticles: Article[];
    topStories: Article[];
    articlesByCategory: CategoryArticles[];
    articles: PaginatedArticles;
    popularTags: Tag[];
    trendingTopics: Tag[];
    filters: {
        tag?: string;
        search?: string;
        source?: number;
    };
}

defineProps<Props>();
</script>

<template>
    <Head title="Crime News - Streetcode.net">
        <meta
            name="description"
            head-key="description"
            content="Latest crime news and stories. Browse by category and location."
        />
    </Head>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Top Section: Hero + Sidebar -->
        <div class="grid gap-8 lg:grid-cols-3">
            <!-- Main Column -->
            <div class="lg:col-span-2">
                <!-- Hero Briefing Section -->
                <HeroBriefing
                    :hero-article="heroArticle"
                    :featured-articles="featuredArticles"
                />

                <!-- Top Stories List -->
                <TopStoriesList
                    v-if="topStories.length"
                    :articles="topStories"
                />
            </div>

            <!-- Sidebar -->
            <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
                <!-- Crime Categories -->
                <TrendingTopics
                    v-if="popularTags.length"
                    :topics="popularTags"
                    title="Categories"
                />

                <!-- Newsletter Signup -->
                <NewsletterSignup />
            </aside>
        </div>

        <!-- Full Width Sections -->
        <div class="mt-8">
            <!-- Topic Sections -->
            <TopicSection
                v-for="category in articlesByCategory"
                :key="category.tag.id"
                :tag="category.tag"
                :articles="category.articles"
            />

            <!-- Tag Filters -->
            <section v-if="popularTags.length" class="mb-8">
                <h3
                    class="mb-4 font-heading text-lg font-bold text-public-text"
                >
                    Browse by Category
                </h3>
                <TagFilter :tags="popularTags" :active-tag="filters.tag" />
            </section>

            <!-- All Articles Grid -->
            <section>
                <h2
                    class="mb-4 font-heading text-lg font-bold text-public-text"
                >
                    Latest News Stories
                </h2>
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
                    No articles found. Try adjusting your filters.
                </div>

                <ArticlePagination
                    v-if="
                        articles?.meta?.last_page && articles.meta.last_page > 1
                    "
                    :current-page="articles.meta.current_page"
                    :last-page="articles.meta.last_page"
                    route="/"
                    :filters="filters"
                />
            </section>
        </div>
    </main>
</template>

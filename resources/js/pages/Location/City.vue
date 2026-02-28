<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticlePagination from '@/components/ArticlePagination.vue';
import HeroBriefing from '@/components/HeroBriefing.vue';
import LocationHeader from '@/components/LocationHeader.vue';
import NearbyCities from '@/components/NearbyCities.vue';
import NewsletterSignup from '@/components/NewsletterSignup.vue';
import TagFilter from '@/components/TagFilter.vue';
import TopStoriesList from '@/components/TopStoriesList.vue';
import TrendingTopics from '@/components/TrendingTopics.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type {
    Article,
    City as CityType,
    LocationContext,
    PaginatedArticles,
    Tag,
} from '@/types';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: PublicLayout });

interface Props {
    location: LocationContext;
    city: CityType;
    heroArticle: Article | null;
    featuredArticles: Article[];
    topStories: Article[];
    articles: PaginatedArticles;
    popularTags: Tag[];
    nearbyCities: CityType[];
    filters: {
        tag?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const currentPath = `/crime/${props.location.country}/${props.location.region}/${props.location.city}`;
</script>

<template>
    <Head
        :title="`${location.cityName} Crime News - ${location.regionName} | Streetcode.net`"
    >
        <meta
            name="description"
            head-key="description"
            :content="`Crime news in ${location.cityName}, ${location.regionName}. Latest stories and public safety updates.`"
        />
        <meta property="og:type" content="website" />
        <meta
            property="og:title"
            :content="`${location.cityName} Crime News`"
        />
        <meta
            property="og:description"
            :content="`Crime news in ${location.cityName}, ${location.regionName}. Latest stories and public safety updates.`"
        />
        <meta property="og:image" content="/logo.png" />
    </Head>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <LocationHeader
            :location="location"
            :article-count="articles?.meta?.total ?? articles.total"
        />

        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <HeroBriefing
                    :hero-article="heroArticle"
                    :featured-articles="featuredArticles"
                />

                <TopStoriesList
                    v-if="topStories.length"
                    :articles="topStories"
                />
            </div>

            <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
                <NearbyCities
                    v-if="nearbyCities.length"
                    :cities="nearbyCities"
                    :current-slug="location.city"
                />

                <TrendingTopics
                    v-if="popularTags.length"
                    :topics="popularTags"
                    title="Categories"
                />

                <NewsletterSignup />
            </aside>
        </div>

        <div class="mt-8">
            <section v-if="popularTags.length" class="mb-8">
                <h3
                    class="mb-4 font-heading text-lg font-bold text-public-text"
                >
                    Browse by Category
                </h3>
                <TagFilter
                    :tags="popularTags"
                    :active-tag="filters.tag"
                    :route="currentPath"
                />
            </section>

            <section>
                <h2
                    class="mb-4 font-heading text-lg font-bold text-public-text"
                >
                    Latest from {{ location.cityName }}
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
                    :route="currentPath"
                    :filters="filters"
                />
            </section>
        </div>
    </main>
</template>

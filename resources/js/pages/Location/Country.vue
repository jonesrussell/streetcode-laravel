<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticlePagination from '@/components/ArticlePagination.vue';
import HeroBriefing from '@/components/HeroBriefing.vue';
import LocationHeader from '@/components/LocationHeader.vue';
import NewsletterSignup from '@/components/NewsletterSignup.vue';
import TagFilter from '@/components/TagFilter.vue';
import TopCities from '@/components/TopCities.vue';
import TopRegions from '@/components/TopRegions.vue';
import TopStoriesList from '@/components/TopStoriesList.vue';
import TrendingTopics from '@/components/TrendingTopics.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Article, City, LocationContext, PaginatedArticles, Tag } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

defineOptions({ layout: PublicLayout });

interface RegionStat {
    region_code: string;
    region_name: string;
    total_articles: number;
    city_count: number;
}

interface Props {
    location: LocationContext;
    heroArticle: Article | null;
    featuredArticles: Article[];
    topStories: Article[];
    regions: RegionStat[];
    allRegions?: RegionStat[];
    totalRegionCount: number;
    topCities: City[];
    articles: PaginatedArticles;
    popularTags: Tag[];
    filters: {
        tag?: string;
    };
}

const props = defineProps<Props>();

const currentPath = `/crime/${props.location.country}`;

const showAllRegions = ref(false);

const allRegions = toRef(props, 'allRegions');
const allRegionsData = computed(() => allRegions.value ?? props.regions);
</script>

<template>
    <Head :title="`${location.countryName} Crime News | Streetcode.net`">
        <meta
            name="description"
            head-key="description"
            :content="`Crime news in ${location.countryName}. Latest stories and public safety updates.`"
        />
        <meta property="og:type" content="website" />
        <meta
            property="og:title"
            :content="`${location.countryName} Crime News`"
        />
        <meta
            property="og:description"
            :content="`Crime news in ${location.countryName}. Latest stories and public safety updates.`"
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
                <TopRegions
                    v-if="regions.length"
                    :regions="regions"
                    :country-code="location.country"
                    :total-count="totalRegionCount"
                >
                    <template
                        v-if="totalRegionCount > regions.length"
                        #footer
                    >
                        <button
                            type="button"
                            class="mt-3 flex w-full items-center justify-center gap-1 rounded-md border border-public-border px-3 py-2 text-sm text-public-text-secondary transition-colors hover:bg-public-bg-subtle hover:text-public-accent"
                            @click="showAllRegions = true"
                        >
                            View all {{ totalRegionCount }} regions
                            <ChevronRight class="size-3" />
                        </button>
                    </template>
                </TopRegions>

                <TopCities v-if="topCities.length" :cities="topCities" />

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
                <h3 class="mb-4 font-heading text-lg font-bold text-public-text">
                    Browse by Category
                </h3>
                <TagFilter
                    :tags="popularTags"
                    :active-tag="filters.tag"
                    :route="currentPath"
                />
            </section>

            <section>
                <h2 class="mb-4 font-heading text-lg font-bold text-public-text">
                    Latest from {{ location.countryName }}
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
                    No articles found.
                </div>

                <ArticlePagination
                    v-if="articles?.meta?.last_page && articles.meta.last_page > 1"
                    :current-page="articles.meta.current_page"
                    :last-page="articles.meta.last_page"
                    :route="currentPath"
                    :filters="filters"
                />
            </section>
        </div>

        <!-- All Regions Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                leave-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showAllRegions"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    @click.self="showAllRegions = false"
                >
                    <div
                        class="max-h-[80vh] w-full max-w-2xl overflow-hidden rounded-lg bg-public-surface shadow-xl"
                    >
                        <div
                            class="flex items-center justify-between border-b border-public-border px-6 py-4"
                        >
                            <h2
                                class="font-heading text-xl font-bold text-public-text"
                            >
                                All Regions in {{ location.countryName }}
                            </h2>
                            <button
                                type="button"
                                class="text-public-text-muted hover:text-public-text"
                                @click="showAllRegions = false"
                            >
                                <span class="sr-only">Close</span>
                                <svg
                                    class="size-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto p-6">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <Link
                                    v-for="region in allRegionsData"
                                    :key="region.region_code"
                                    :href="`/crime/${location.country}/${region.region_code.toLowerCase()}`"
                                    class="flex items-center justify-between rounded-md border border-public-border px-4 py-3 transition-colors hover:bg-public-bg-subtle hover:text-public-accent"
                                >
                                    <span class="text-public-text">
                                        {{ region.region_name }}
                                    </span>
                                    <span
                                        class="text-xs text-public-text-muted"
                                    >
                                        {{ region.total_articles }} articles
                                    </span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>

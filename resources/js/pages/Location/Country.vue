<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticlePagination from '@/components/ArticlePagination.vue';
import LocationHeader from '@/components/LocationHeader.vue';
import TagFilter from '@/components/TagFilter.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { City, LocationContext, PaginatedArticles, Tag } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Globe, MapPin } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

interface RegionStat {
    region_code: string;
    region_name: string;
    total_articles: number;
    city_count: number;
}

interface Props {
    location: LocationContext;
    regions: RegionStat[];
    topCities: City[];
    articles: PaginatedArticles;
    popularTags: Tag[];
    filters: {
        tag?: string;
    };
}

const props = defineProps<Props>();

const currentPath = `/crime/${props.location.country}`;
</script>

<template>
    <Head :title="`${location.countryName} Crime News | Streetcode.net`">
        <meta
            name="description"
            head-key="description"
            :content="`Crime news in ${location.countryName}. Latest stories and public safety updates.`"
        />
    </Head>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <LocationHeader
            :location="location"
            :article-count="articles?.meta?.total ?? articles.total"
        />

        <!-- Regions Grid -->
        <section v-if="regions.length" class="mb-12">
            <h2 class="mb-6 font-heading text-2xl font-bold text-public-text">
                Browse by Region
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="region in regions"
                    :key="region.region_code"
                    :href="`/crime/${location.country}/${region.region_code.toLowerCase()}`"
                >
                    <Card
                        class="border-public-border bg-public-surface transition-all hover:shadow-md"
                    >
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <Globe class="size-5 text-public-accent" />
                                    <CardTitle class="text-lg text-public-text">
                                        {{ region.region_name }}
                                    </CardTitle>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <Badge
                                        variant="secondary"
                                        class="bg-public-bg-subtle text-public-text-secondary"
                                    >
                                        {{ region.total_articles }}
                                        articles
                                    </Badge>
                                    <span
                                        class="text-xs text-public-text-muted"
                                    >
                                        {{ region.city_count }}
                                        {{
                                            region.city_count === 1
                                                ? 'city'
                                                : 'cities'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </CardHeader>
                    </Card>
                </Link>
            </div>
        </section>

        <!-- Top Cities -->
        <section v-if="topCities.length" class="mb-12">
            <h2 class="mb-6 font-heading text-2xl font-bold text-public-text">
                Most Active Cities
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="city in topCities"
                    :key="city.id"
                    :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                    class="group flex items-center gap-3 rounded-lg border border-public-border bg-public-surface p-4 transition-all hover:shadow-md"
                >
                    <MapPin class="size-5 shrink-0 text-public-accent" />
                    <div class="min-w-0">
                        <p
                            class="truncate font-medium text-public-text group-hover:text-public-accent"
                        >
                            {{ city.city_name }}
                        </p>
                        <p class="text-xs text-public-text-muted">
                            {{ city.region_name }} &middot;
                            {{ city.article_count }} articles
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Tag Filter -->
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

        <!-- Latest Articles -->
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
    </main>
</template>

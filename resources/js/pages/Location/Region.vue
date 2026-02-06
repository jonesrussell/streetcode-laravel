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
import { MapPin } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

interface Props {
    location: LocationContext;
    cities: City[];
    articles: PaginatedArticles;
    popularTags: Tag[];
    filters: {
        tag?: string;
    };
}

const props = defineProps<Props>();

const currentPath = `/crime/${props.location.country}/${props.location.region}`;
</script>

<template>
    <Head
        :title="`${location.regionName} Crime News - ${location.countryName} | Streetcode.net`"
    />

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <LocationHeader
            :location="location"
            :article-count="articles?.meta?.total ?? articles.total"
        />

        <!-- Cities Grid -->
        <section v-if="cities.length" class="mb-12">
            <h2
                class="mb-6 font-heading text-2xl font-bold text-public-text"
            >
                Cities in {{ location.regionName }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="city in cities"
                    :key="city.id"
                    :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                >
                    <Card
                        class="border-public-border bg-public-surface transition-all hover:shadow-md"
                    >
                        <CardHeader>
                            <div
                                class="flex items-start justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <MapPin
                                        class="size-5 text-public-accent"
                                    />
                                    <CardTitle
                                        class="text-lg text-public-text"
                                    >
                                        {{ city.city_name }}
                                    </CardTitle>
                                </div>
                                <Badge
                                    variant="secondary"
                                    class="bg-public-bg-subtle text-public-text-secondary"
                                >
                                    {{ city.article_count }}
                                </Badge>
                            </div>
                        </CardHeader>
                    </Card>
                </Link>
            </div>
        </section>

        <!-- Tag Filter -->
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

        <!-- Latest Articles -->
        <section>
            <h2
                class="mb-4 font-heading text-lg font-bold text-public-text"
            >
                Latest from {{ location.regionName }}
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
                    articles?.meta?.last_page &&
                    articles.meta.last_page > 1
                "
                :current-page="articles.meta.current_page"
                :last-page="articles.meta.last_page"
                :route="currentPath"
                :filters="filters"
            />
        </section>
    </main>
</template>

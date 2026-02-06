<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ArticleCard from '@/components/ArticleCard.vue';
import LocationHeader from '@/components/LocationHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import TagFilter from '@/components/TagFilter.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import type { City, LocationContext, PaginatedArticles, Tag } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Menu } from 'lucide-vue-next';
import { ref } from 'vue';

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

const showMobileMenu = ref(false);

const currentPath = `/crime/${props.location.country}/${props.location.region}`;
</script>

<template>
    <Head
        :title="`${location.regionName} Crime News - ${location.countryName} | Streetcode.net`"
    />

    <div class="min-h-screen bg-zinc-900">
        <header class="sticky top-0 z-50 border-b border-zinc-800 bg-zinc-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link
                        href="/"
                        class="flex h-16 shrink-0 items-center p-2 hover:opacity-90"
                    >
                        <AppLogoIcon
                            class="max-h-full w-auto object-contain dark:invert"
                        />
                    </Link>

                    <nav class="hidden items-center gap-6 md:flex">
                        <Link
                            href="/"
                            class="text-sm text-zinc-300 hover:text-white"
                            >Home</Link
                        >
                        <Link
                            :href="`/crime/${location.country}`"
                            class="text-sm text-zinc-300 hover:text-white"
                            >{{ location.countryName }}</Link
                        >
                        <span class="text-sm font-medium text-white">{{
                            location.regionName
                        }}</span>
                    </nav>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-zinc-400 hover:text-white md:hidden"
                        @click="showMobileMenu = !showMobileMenu"
                    >
                        <Menu class="size-5" />
                    </Button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <LocationHeader
                :location="location"
                :article-count="articles?.meta?.total ?? articles.total"
            />

            <!-- Cities Grid -->
            <section v-if="cities.length" class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-white">
                    Cities in {{ location.regionName }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="city in cities"
                        :key="city.id"
                        :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                    >
                        <Card
                            class="border-zinc-700 bg-zinc-800/50 transition-all hover:bg-zinc-800 hover:shadow-lg"
                        >
                            <CardHeader>
                                <div
                                    class="flex items-start justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <MapPin
                                            class="size-5 text-red-400"
                                        />
                                        <CardTitle
                                            class="text-lg text-white"
                                        >
                                            {{ city.city_name }}
                                        </CardTitle>
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        class="bg-zinc-700"
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
                <h3 class="mb-4 text-lg font-bold text-white">
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
                <h2 class="mb-4 text-lg font-bold text-white">
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
                    class="rounded-lg bg-zinc-800/50 py-12 text-center text-zinc-400"
                >
                    No articles found. Try adjusting your filters.
                </div>

                <div
                    v-if="
                        articles?.meta?.last_page &&
                        articles.meta.last_page > 1
                    "
                    class="mt-8 flex justify-center gap-2"
                >
                    <Button
                        v-for="page in Math.min(
                            articles.meta.last_page,
                            10,
                        )"
                        :key="page"
                        :variant="
                            page === articles.meta.current_page
                                ? 'default'
                                : 'outline'
                        "
                        size="sm"
                        class="border-zinc-700"
                        @click="
                            router.get(currentPath, { ...filters, page })
                        "
                    >
                        {{ page }}
                    </Button>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>

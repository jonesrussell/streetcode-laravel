<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ArticleCard from '@/components/ArticleCard.vue';
import HeroBriefing from '@/components/HeroBriefing.vue';
import LocationHeader from '@/components/LocationHeader.vue';
import NearbyCities from '@/components/NearbyCities.vue';
import NewsletterSignup from '@/components/NewsletterSignup.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import TagFilter from '@/components/TagFilter.vue';
import TopStoriesList from '@/components/TopStoriesList.vue';
import TrendingTopics from '@/components/TrendingTopics.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    Article,
    City as CityType,
    LocationContext,
    PaginatedArticles,
    Tag,
} from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Menu, Search } from 'lucide-vue-next';
import { ref } from 'vue';

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

const searchQuery = ref(props.filters.search || '');
const showMobileMenu = ref(false);

const currentPath = `/crime/${props.location.country}/${props.location.region}/${props.location.city}`;

const performSearch = () => {
    router.get(
        currentPath,
        { search: searchQuery.value },
        { preserveState: true },
    );
};
</script>

<template>
    <Head
        :title="`${location.cityName} Crime News - ${location.regionName} | Streetcode.net`"
    />

    <div class="min-h-screen bg-zinc-900">
        <!-- Header -->
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
                        <Link
                            :href="`/crime/${location.country}/${location.region}`"
                            class="text-sm text-zinc-300 hover:text-white"
                            >{{ location.regionName }}</Link
                        >
                        <span class="text-sm font-medium text-white">{{
                            location.cityName
                        }}</span>
                    </nav>

                    <div class="flex items-center gap-4">
                        <div class="relative hidden md:block">
                            <Search
                                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-500"
                            />
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search..."
                                class="w-64 border-zinc-700 bg-zinc-900 pl-10 text-white placeholder:text-zinc-500"
                                @keyup.enter="performSearch"
                            />
                        </div>
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

                <div
                    v-if="showMobileMenu"
                    class="border-t border-zinc-800 py-4 md:hidden"
                >
                    <div class="mb-4">
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search articles..."
                            class="w-full border-zinc-700 bg-zinc-900 text-white placeholder:text-zinc-500"
                            @keyup.enter="performSearch"
                        />
                    </div>
                    <nav class="flex flex-col gap-2">
                        <Link
                            href="/"
                            class="rounded px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                            >Home</Link
                        >
                        <Link
                            :href="`/crime/${location.country}`"
                            class="rounded px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                            >{{ location.countryName }}</Link
                        >
                        <Link
                            :href="`/crime/${location.country}/${location.region}`"
                            class="rounded px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                            >{{ location.regionName }}</Link
                        >
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
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

                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
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
                    <h3 class="mb-4 text-lg font-bold text-white">
                        Browse by Category
                    </h3>
                    <TagFilter
                        :tags="popularTags"
                        :active-tag="filters.tag"
                        :route="currentPath"
                    />
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-bold text-white">
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
                                router.get(currentPath, {
                                    ...filters,
                                    page,
                                })
                            "
                        >
                            {{ page }}
                        </Button>
                    </div>
                </section>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

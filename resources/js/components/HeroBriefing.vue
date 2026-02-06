<script setup lang="ts">
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
import { formatTimeAgo } from '@/composables/useTimeAgo';
import type { Article } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';

interface Props {
    heroArticle: Article | null;
    featuredArticles: Article[];
}

defineProps<Props>();
</script>

<template>
    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-xl font-bold text-public-text">
                Daily Briefing
            </h2>
            <span class="text-sm text-public-text-muted">
                {{
                    new Date().toLocaleDateString('en-CA', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                    })
                }}
            </span>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Hero Article (Large) -->
            <div v-if="heroArticle" class="lg:col-span-2">
                <Link
                    :href="`/articles/${heroArticle.id}`"
                    class="group relative block overflow-hidden rounded-lg bg-public-surface"
                >
                    <div class="aspect-[16/9] w-full">
                        <img
                            v-if="heroArticle.image_url"
                            :src="heroArticle.image_url"
                            :alt="heroArticle.title"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-public-bg-subtle"
                        >
                            <span class="text-public-text-muted">No image</span>
                        </div>
                    </div>
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"
                    />
                    <div class="absolute right-0 bottom-0 left-0 p-6">
                        <div class="mb-3 flex items-center gap-3">
                            <SourceCredibilityBadge
                                v-if="heroArticle.news_source"
                                :source="heroArticle.news_source"
                            />
                            <div
                                class="flex items-center gap-1 text-xs text-white/80"
                            >
                                <Clock class="size-3" />
                                {{ formatTimeAgo(heroArticle.published_at) }}
                            </div>
                        </div>
                        <h3
                            class="mb-2 font-heading text-2xl leading-tight font-bold text-white group-hover:text-white/90"
                        >
                            {{ heroArticle.title }}
                        </h3>
                        <p
                            v-if="heroArticle.excerpt"
                            class="line-clamp-2 text-sm text-white/80"
                        >
                            {{ heroArticle.excerpt }}
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Featured Articles (Smaller) -->
            <div class="flex flex-col gap-4">
                <Link
                    v-for="article in featuredArticles.slice(0, 3)"
                    :key="article.id"
                    :href="`/articles/${article.id}`"
                    class="group flex gap-3 rounded-lg border border-public-border bg-public-surface p-3 transition-colors hover:border-public-accent/30"
                >
                    <div
                        v-if="article.image_url"
                        class="size-20 shrink-0 overflow-hidden rounded"
                    >
                        <img
                            :src="article.image_url"
                            :alt="article.title"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="flex min-w-0 flex-col justify-center">
                        <div class="mb-1 flex items-center gap-2">
                            <span
                                v-if="article.news_source"
                                class="text-xs text-public-text-muted"
                            >
                                {{ article.news_source.name }}
                            </span>
                            <span class="text-xs text-public-text-muted">{{
                                formatTimeAgo(article.published_at)
                            }}</span>
                        </div>
                        <h4
                            class="line-clamp-2 font-heading text-sm leading-snug font-medium text-public-text group-hover:text-public-accent"
                        >
                            {{ article.title }}
                        </h4>
                        <div
                            v-if="article.tags?.length"
                            class="mt-2 flex flex-wrap gap-1"
                        >
                            <Badge
                                v-for="tag in article.tags.slice(0, 2)"
                                :key="tag.id"
                                variant="secondary"
                                class="h-5 bg-public-bg-subtle px-1.5 text-[10px] text-public-text-muted"
                            >
                                {{ tag.name }}
                            </Badge>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>

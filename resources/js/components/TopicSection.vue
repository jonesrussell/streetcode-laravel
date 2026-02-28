<script setup lang="ts">
import ArticleImage from '@/components/ArticleImage.vue';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
import { getTagBadgeColor } from '@/composables/useTagColors';
import { formatTimeAgo } from '@/composables/useTimeAgo';
import type { Article, Tag } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Clock } from 'lucide-vue-next';

interface Props {
    tag: Tag;
    articles: Article[];
}

defineProps<Props>();
</script>

<template>
    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-heading text-lg font-bold text-public-text">
                    {{ tag.name }} News
                </h2>
                <Badge :class="['border', getTagBadgeColor(tag.color)]">
                    {{ tag.article_count }} articles
                </Badge>
            </div>
            <Link
                :href="`/tags/${tag.slug}`"
                class="inline-flex items-center text-sm font-medium text-public-accent hover:text-public-accent-hover"
            >
                Read more {{ tag.name }} news
                <ChevronRight class="ml-1 size-4" />
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="(article, index) in articles"
                :key="article.id"
                :href="`/articles/${article.slug}`"
                class="group overflow-hidden rounded-lg border border-public-border bg-public-surface transition-all hover:shadow-md"
            >
                <div
                    v-if="article.image_url"
                    class="aspect-video w-full overflow-hidden"
                >
                    <ArticleImage
                        :src="article.image_url"
                        :alt="article.title"
                        :loading="index === 0 ? 'eager' : 'lazy'"
                        container-class="h-full w-full"
                        img-class="transition-transform duration-300 group-hover:scale-105"
                    />
                </div>
                <div class="p-4">
                    <h3
                        class="mb-2 line-clamp-2 text-sm leading-snug font-medium text-public-text group-hover:text-public-accent"
                    >
                        {{ article.title }}
                    </h3>
                    <div
                        class="flex items-center justify-between text-xs text-public-text-muted"
                    >
                        <SourceCredibilityBadge
                            v-if="article.news_source"
                            :source="article.news_source"
                        />
                        <div class="flex items-center gap-1">
                            <Clock class="size-3" />
                            {{ formatTimeAgo(article.published_at) }}
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    </section>
</template>

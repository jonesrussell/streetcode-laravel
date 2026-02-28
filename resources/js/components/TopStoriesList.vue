<script setup lang="ts">
import ArticleImage from '@/components/ArticleImage.vue';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { formatTimeAgo } from '@/composables/useTimeAgo';
import type { Article } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';

interface Props {
    articles: Article[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Top News Stories',
});
</script>

<template>
    <section class="mb-8">
        <h2 class="mb-4 font-heading text-lg font-bold text-public-text">
            {{ title }}
        </h2>

        <div class="divide-y divide-public-border">
            <Link
                v-for="(article, index) in articles"
                :key="article.id"
                :href="`/articles/${article.slug}`"
                class="group flex items-center gap-4 py-3 transition-colors hover:bg-public-bg-subtle"
            >
                <!-- Rank Number -->
                <span
                    class="w-6 shrink-0 text-center font-heading text-lg font-bold text-public-accent"
                >
                    {{ index + 1 }}
                </span>

                <div class="flex min-w-0 flex-1 flex-col">
                    <h3
                        class="mb-1 line-clamp-2 text-sm leading-snug font-medium text-public-text group-hover:text-public-accent"
                    >
                        {{ article.title }}
                    </h3>
                    <div
                        class="flex items-center gap-3 text-xs text-public-text-muted"
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
                <div
                    v-if="article.image_url"
                    class="size-16 shrink-0 overflow-hidden rounded"
                >
                    <ArticleImage
                        :src="article.image_url"
                        :alt="article.title"
                        container-class="size-full"
                        img-class="transition-transform duration-300 group-hover:scale-110"
                    />
                </div>
            </Link>
        </div>
    </section>
</template>

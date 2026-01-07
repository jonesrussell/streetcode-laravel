<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Article } from '@/types';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Clock, ExternalLink } from 'lucide-vue-next';

interface Props {
    articles: Article[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Top News Stories',
});

const formatTimeAgo = (dateString: string | null): string => {
    if (!dateString) {
        return '';
    }
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);

    if (diffHours < 1) {
        return 'Just now';
    }
    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }
    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }
    return date.toLocaleDateString('en-CA', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <section class="mb-8">
        <h2 class="mb-4 text-lg font-bold text-white">{{ title }}</h2>

        <div class="space-y-2">
            <Link
                v-for="article in articles"
                :key="article.id"
                :href="`/articles/${article.id}`"
                class="group flex items-center gap-4 rounded-lg px-3 py-2 transition-colors hover:bg-zinc-800/50"
            >
                <div class="flex min-w-0 flex-1 flex-col">
                    <h3 class="mb-1 line-clamp-2 text-sm font-medium leading-snug text-white group-hover:text-zinc-200">
                        {{ article.title }}
                    </h3>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <SourceCredibilityBadge v-if="article.news_source" :source="article.news_source" />
                        <div class="flex items-center gap-1">
                            <Clock class="size-3" />
                            {{ formatTimeAgo(article.published_at) }}
                        </div>
                    </div>
                </div>
                <div v-if="article.image_url" class="size-16 shrink-0 overflow-hidden rounded">
                    <img
                        :src="article.image_url"
                        :alt="article.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                    />
                </div>
            </Link>
        </div>
    </section>
</template>

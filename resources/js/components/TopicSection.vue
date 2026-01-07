<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Article, Tag } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Clock, ChevronRight } from 'lucide-vue-next';

interface Props {
    tag: Tag;
    articles: Article[];
}

defineProps<Props>();

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

const tagColorMap: Record<string, string> = {
    red: 'bg-red-500/20 text-red-400 border-red-500/30',
    orange: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
    yellow: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
    green: 'bg-green-500/20 text-green-400 border-green-500/30',
    blue: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    purple: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
};

const getTagColorClass = (color: string | null): string => {
    return tagColorMap[color || ''] || 'bg-zinc-500/20 text-zinc-400 border-zinc-500/30';
};
</script>

<template>
    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-white">{{ tag.name }} News</h2>
                <Badge :class="['border', getTagColorClass(tag.color)]">
                    {{ tag.article_count }} articles
                </Badge>
            </div>
            <Link :href="`/?tag=${tag.slug}`">
                <Button variant="ghost" size="sm" class="text-zinc-400 hover:text-white">
                    Read More
                    <ChevronRight class="ml-1 size-4" />
                </Button>
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="article in articles"
                :key="article.id"
                :href="`/articles/${article.id}`"
                class="group overflow-hidden rounded-lg bg-zinc-800/50 transition-colors hover:bg-zinc-800"
            >
                <div v-if="article.image_url" class="aspect-video w-full overflow-hidden">
                    <img
                        :src="article.image_url"
                        :alt="article.title"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                </div>
                <div class="p-4">
                    <h3 class="mb-2 line-clamp-2 text-sm font-medium leading-snug text-white group-hover:text-zinc-200">
                        {{ article.title }}
                    </h3>
                    <div class="flex items-center justify-between text-xs text-zinc-400">
                        <SourceCredibilityBadge v-if="article.news_source" :source="article.news_source" />
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

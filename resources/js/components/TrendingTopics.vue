<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Tag } from '@/types';
import { TrendingUp, Plus } from 'lucide-vue-next';

interface Props {
    topics: Tag[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Trending Topics',
});

const tagColorMap: Record<string, string> = {
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
};

const getTagColor = (color: string | null): string => {
    return tagColorMap[color || ''] || 'bg-zinc-500';
};
</script>

<template>
    <div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">
        <div class="mb-4 flex items-center gap-2">
            <TrendingUp class="size-4 text-zinc-400" />
            <h3 class="font-semibold text-white">{{ title }}</h3>
        </div>

        <div class="space-y-2">
            <Link
                v-for="topic in topics"
                :key="topic.id"
                :href="`/?tag=${topic.slug}`"
                class="group flex items-center justify-between rounded-lg px-3 py-2 transition-colors hover:bg-zinc-700/50"
            >
                <div class="flex items-center gap-2">
                    <div :class="['size-2 rounded-full', getTagColor(topic.color)]" />
                    <span class="text-sm text-zinc-300 group-hover:text-white">
                        {{ topic.name }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-500">{{ topic.article_count }}</span>
                    <Plus class="size-4 text-zinc-500 opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
            </Link>
        </div>
    </div>
</template>

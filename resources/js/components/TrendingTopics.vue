<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/TagController';
import { getTagDotColor } from '@/composables/useTagColors';
import type { Tag } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, TrendingUp } from 'lucide-vue-next';

interface Props {
    topics: Tag[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Trending Topics',
});
</script>

<template>
    <div class="rounded-lg border border-public-border bg-public-surface p-4">
        <div class="mb-4 flex items-center gap-2">
            <TrendingUp class="size-4 text-public-text-muted" />
            <h3 class="font-heading font-semibold text-public-text">
                {{ title }}
            </h3>
        </div>

        <div class="space-y-1">
            <Link
                v-for="topic in topics"
                :key="topic.id"
                :href="show.url(topic)"
                class="group flex items-center justify-between rounded-md px-3 py-2 transition-colors hover:bg-public-bg-subtle"
            >
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'size-2 rounded-full',
                            getTagDotColor(topic.color),
                        ]"
                    />
                    <span
                        class="text-sm text-public-text-secondary group-hover:text-public-accent"
                    >
                        {{ topic.name }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-public-text-muted">{{
                        topic.article_count
                    }}</span>
                    <ChevronRight
                        class="size-3 text-public-text-muted opacity-0 transition-opacity group-hover:opacity-100"
                    />
                </div>
            </Link>
        </div>
    </div>
</template>

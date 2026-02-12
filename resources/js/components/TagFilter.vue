<script setup lang="ts">
import type { Tag } from '@/types';
import { router } from '@inertiajs/vue3';

interface Props {
    tags: Tag[];
    activeTag?: string;
    route?: string;
}

const props = withDefaults(defineProps<Props>(), {
    route: '/',
});

const selectTag = (tagSlug: string | null) => {
    if (tagSlug) {
        router.get(props.route, { tag: tagSlug }, { preserveState: true });
    } else {
        router.get(props.route, {}, { preserveState: true });
    }
};
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            :class="[
                'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                !activeTag
                    ? 'border-public-accent-button bg-public-accent-button text-white'
                    : 'border-public-border text-public-text-secondary hover:border-public-accent hover:text-public-accent',
            ]"
            @click="selectTag(null)"
        >
            All
        </button>

        <button
            v-for="tag in tags"
            :key="tag.id"
            :class="[
                'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                activeTag === tag.slug
                    ? 'border-public-accent-button bg-public-accent-button text-white'
                    : 'border-public-border text-public-text-secondary hover:border-public-accent hover:text-public-accent',
            ]"
            @click="selectTag(tag.slug)"
        >
            {{ tag.name }}
            <span class="ml-1 text-xs opacity-70"
                >({{ tag.article_count }})</span
            >
        </button>
    </div>
</template>

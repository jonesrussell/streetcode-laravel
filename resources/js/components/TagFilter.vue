<script setup lang="ts">
import type { Tag } from '@/types'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'

interface Props {
    tags: Tag[]
    activeTag?: string
    route?: string
}

const props = withDefaults(defineProps<Props>(), {
    route: '/',
})

const selectTag = (tagSlug: string | null) => {
    if (tagSlug) {
        router.get(props.route, { tag: tagSlug }, { preserveState: true })
    } else {
        router.get(props.route, {}, { preserveState: true })
    }
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            variant="outline"
            size="sm"
            :class="[
                'border-zinc-700',
                !activeTag ? 'bg-red-600 text-white border-red-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'
            ]"
            @click="selectTag(null)"
        >
            All
        </Button>

        <Button
            v-for="tag in tags"
            :key="tag.id"
            variant="outline"
            size="sm"
            :class="[
                'border-zinc-700',
                activeTag === tag.slug ? 'bg-red-600 text-white border-red-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'
            ]"
            @click="selectTag(tag.slug)"
        >
            {{ tag.name }}
            <span class="ml-1.5 text-xs opacity-70">({{ tag.article_count }})</span>
        </Button>
    </div>
</template>

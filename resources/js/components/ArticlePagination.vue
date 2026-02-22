<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    currentPage: number;
    lastPage: number;
    route: string;
    filters?: Record<string, unknown>;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

const goToPage = (page: number) => {
    router.get(props.route, { ...props.filters, page });
};

const pages = computed(() => {
    const total = props.lastPage;
    const current = props.currentPage;
    const delta = 2;
    const range: (number | '...')[] = [];

    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            (i >= current - delta && i <= current + delta)
        ) {
            range.push(i);
        } else if (range[range.length - 1] !== '...') {
            range.push('...');
        }
    }
    return range;
});
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="mt-8 flex items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <button
            :disabled="currentPage <= 1"
            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-public-text-secondary transition-colors hover:bg-public-bg-subtle disabled:pointer-events-none disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
        >
            <ChevronLeft class="size-4" aria-hidden="true" />
            Previous
        </button>

        <template v-for="(page, i) in pages" :key="i">
            <span
                v-if="page === '...'"
                class="px-2 text-sm text-public-text-muted"
            >
                ...
            </span>
            <button
                v-else
                :class="[
                    'inline-flex size-9 items-center justify-center rounded-md text-sm font-medium transition-colors',
                    page === currentPage
                        ? 'bg-public-accent text-white'
                        : 'text-public-text-secondary hover:bg-public-bg-subtle',
                ]"
                :aria-current="page === currentPage ? 'page' : undefined"
                @click="goToPage(page as number)"
            >
                {{ page }}
            </button>
        </template>

        <button
            :disabled="currentPage >= lastPage"
            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-public-text-secondary transition-colors hover:bg-public-bg-subtle disabled:pointer-events-none disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
        >
            Next
            <ChevronRight class="size-4" aria-hidden="true" />
        </button>
    </nav>
</template>

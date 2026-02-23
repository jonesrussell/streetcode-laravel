<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    src: string | null;
    alt: string;
    containerClass?: string;
    imgClass?: string;
    showPlaceholderWhenEmpty?: boolean;
    loading?: 'lazy' | 'eager';
    fit?: 'cover' | 'contain';
    fetchpriority?: 'high' | 'low' | 'auto';
}

const props = withDefaults(defineProps<Props>(), {
    containerClass: '',
    imgClass: '',
    showPlaceholderWhenEmpty: false,
    loading: 'lazy',
    fit: 'cover',
    fetchpriority: 'auto',
});

const loadFailed = ref(false);

const hasContent = computed(
    () =>
        (props.src != null && props.src !== '') ||
        props.showPlaceholderWhenEmpty,
);

const objectFitClass = computed(() =>
    props.fit === 'contain' ? 'object-contain' : 'object-cover',
);

function onError(): void {
    loadFailed.value = true;
}
</script>

<template>
    <div v-if="hasContent" :class="containerClass" class="overflow-hidden">
        <img
            v-if="src && !loadFailed"
            :src="src"
            :alt="alt"
            :loading="loading"
            :fetchpriority="fetchpriority"
            :class="[imgClass, objectFitClass]"
            class="size-full"
            @error="onError"
        />
        <div
            v-else
            class="flex size-full min-h-0 min-w-0 items-center justify-center bg-public-bg-subtle text-public-text-muted"
        >
            <slot name="placeholder">No image</slot>
        </div>
    </div>
    <span v-else class="hidden" aria-hidden="true" />
</template>

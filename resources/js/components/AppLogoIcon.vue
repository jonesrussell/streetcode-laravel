<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const logoUrl = (usePage().props.logo_url as string | undefined) ?? '/logo.png';
</script>

<template>
    <img :src="logoUrl" alt="StreetCode" :class="className" v-bind="$attrs" />
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head>
        <meta
            name="robots"
            content="noindex, nofollow"
        />
    </Head>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>

<script setup lang="ts">
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-public-bg font-body text-public-text">
        <SiteHeader />
        <slot />
        <SiteFooter />
    </div>
</template>

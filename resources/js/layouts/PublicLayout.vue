<script setup lang="ts">
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <meta
            name="description"
            head-key="description"
            content="Crime news aggregation. Stay informed about crime trends and public safety in your community."
        />
    </Head>
    <div class="min-h-screen bg-public-bg font-body text-public-text">
        <SiteHeader />
        <div id="main-content">
            <slot />
        </div>
        <SiteFooter />
    </div>
</template>

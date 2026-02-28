<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Globe } from 'lucide-vue-next';

interface RegionStat {
    region_code: string;
    region_name: string;
    total_articles: number;
    city_count: number;
}

interface Props {
    regions: RegionStat[];
    countryCode: string;
    totalCount?: number;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-public-border bg-public-surface p-4">
        <div class="mb-4 flex items-center gap-2">
            <Globe class="size-4 text-public-text-muted" />
            <h3 class="font-heading font-semibold text-public-text">
                Top Regions
            </h3>
        </div>

        <div class="space-y-1">
            <Link
                v-for="region in regions"
                :key="region.region_code"
                :href="`/crime/${countryCode}/${region.region_code.toLowerCase()}`"
                class="group flex items-center justify-between rounded-md px-3 py-2 transition-colors hover:bg-public-bg-subtle"
            >
                <span
                    class="text-sm text-public-text-secondary group-hover:text-public-accent"
                >
                    {{ region.region_name }}
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-public-text-muted">
                        {{ region.total_articles }}
                    </span>
                    <ChevronRight
                        class="size-3 text-public-text-muted opacity-0 transition-opacity group-hover:opacity-100"
                    />
                </div>
            </Link>
        </div>

        <slot name="footer" />
    </div>
</template>

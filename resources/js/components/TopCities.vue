<script setup lang="ts">
import type { City } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, MapPin } from 'lucide-vue-next';

interface Props {
    cities: City[];
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-public-border bg-public-surface p-4">
        <div class="mb-4 flex items-center gap-2">
            <MapPin class="size-4 text-public-text-muted" />
            <h3 class="font-heading font-semibold text-public-text">
                Top Cities
            </h3>
        </div>

        <div class="space-y-1">
            <Link
                v-for="city in cities"
                :key="city.id"
                :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                class="group flex items-center justify-between rounded-md px-3 py-2 transition-colors hover:bg-public-bg-subtle"
            >
                <div class="min-w-0 flex-1">
                    <span
                        class="text-sm text-public-text-secondary group-hover:text-public-accent"
                    >
                        {{ city.city_name }}
                    </span>
                    <span class="ml-1 text-xs text-public-text-muted">
                        {{ city.region_name }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-public-text-muted">
                        {{ city.article_count }}
                    </span>
                    <ChevronRight
                        class="size-3 text-public-text-muted opacity-0 transition-opacity group-hover:opacity-100"
                    />
                </div>
            </Link>
        </div>
    </div>
</template>

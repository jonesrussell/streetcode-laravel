<script setup lang="ts">
import type { City } from '@/types';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';

interface Props {
    cities: City[];
    currentSlug?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-public-border bg-public-surface p-4">
        <div class="mb-4 flex items-center gap-2">
            <MapPin class="size-4 text-public-text-muted" />
            <h3 class="font-heading font-semibold text-public-text">
                Nearby Cities
            </h3>
        </div>

        <div class="space-y-1">
            <Link
                v-for="city in cities"
                :key="city.id"
                :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                class="group flex items-center justify-between rounded-md px-3 py-2 text-sm transition-colors hover:bg-public-bg-subtle"
                :class="
                    city.city_slug === currentSlug
                        ? 'bg-public-accent-subtle font-medium text-public-accent'
                        : 'text-public-text-secondary'
                "
            >
                <span class="group-hover:text-public-accent">
                    {{ city.city_name }}
                </span>
                <span class="text-xs text-public-text-muted">
                    {{ city.article_count }}
                </span>
            </Link>
        </div>
    </div>
</template>

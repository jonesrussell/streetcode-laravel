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
    <div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">
        <div class="mb-4 flex items-center gap-2">
            <MapPin class="size-4 text-zinc-400" />
            <h3 class="font-semibold text-white">Nearby Cities</h3>
        </div>

        <div class="space-y-1">
            <Link
                v-for="city in cities"
                :key="city.id"
                :href="`/crime/${city.country_code}/${city.region_code.toLowerCase()}/${city.city_slug}`"
                class="group flex items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors hover:bg-zinc-700/50"
                :class="
                    city.city_slug === currentSlug
                        ? 'bg-zinc-700/50 text-white'
                        : 'text-zinc-300'
                "
            >
                <span class="group-hover:text-white">
                    {{ city.city_name }}
                </span>
                <span class="text-xs text-zinc-500">
                    {{ city.article_count }}
                </span>
            </Link>
        </div>
    </div>
</template>

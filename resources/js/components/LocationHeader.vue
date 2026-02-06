<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { LocationContext } from '@/types';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    location: LocationContext;
    articleCount?: number;
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => {
    const crumbs: Array<{ name: string; href: string | null }> = [
        { name: 'Home', href: '/' },
        {
            name: props.location.countryName,
            href:
                props.location.level !== 'country'
                    ? `/crime/${props.location.country}`
                    : null,
        },
    ];

    if (props.location.region && props.location.regionName) {
        crumbs.push({
            name: props.location.regionName,
            href:
                props.location.level !== 'region'
                    ? `/crime/${props.location.country}/${props.location.region}`
                    : null,
        });
    }

    if (props.location.city && props.location.cityName) {
        crumbs.push({
            name: props.location.cityName,
            href: null,
        });
    }

    return crumbs;
});

const displayName = computed(() => {
    if (props.location.cityName) return props.location.cityName;
    if (props.location.regionName) return props.location.regionName;
    return props.location.countryName;
});

const subtitle = computed(() => {
    if (props.location.level === 'city') {
        return `${props.location.regionName}, ${props.location.countryName}`;
    }
    if (props.location.level === 'region') {
        return props.location.countryName;
    }
    return null;
});
</script>

<template>
    <div class="mb-8">
        <Breadcrumb class="mb-4">
            <BreadcrumbList>
                <template v-for="(crumb, i) in breadcrumbs" :key="i">
                    <BreadcrumbItem>
                        <BreadcrumbLink v-if="crumb.href" as-child>
                            <Link
                                :href="crumb.href"
                                class="text-zinc-400 hover:text-white"
                            >
                                {{ crumb.name }}
                            </Link>
                        </BreadcrumbLink>
                        <BreadcrumbPage v-else class="text-white">
                            {{ crumb.name }}
                        </BreadcrumbPage>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator
                        v-if="i < breadcrumbs.length - 1"
                    />
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <div class="flex items-center gap-3">
            <MapPin class="size-8 text-red-400" />
            <div>
                <h1 class="text-3xl font-bold text-white">
                    {{ displayName }}
                </h1>
                <div
                    class="mt-1 flex items-center gap-2 text-sm text-zinc-400"
                >
                    <span v-if="subtitle">{{ subtitle }}</span>
                    <Badge
                        v-if="articleCount"
                        variant="secondary"
                        class="bg-zinc-700 text-zinc-300"
                    >
                        {{ articleCount.toLocaleString() }} articles
                    </Badge>
                </div>
            </div>
        </div>
    </div>
</template>

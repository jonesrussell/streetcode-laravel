<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/TagController';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { getTagBadgeColor, getTagDotColor } from '@/composables/useTagColors';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Tag } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Tag as TagIcon } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

interface Props {
    crimeCategories: Tag[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Crime Categories | Streetcode.net">
        <meta
            name="description"
            head-key="description"
            content="Browse crime news by category. Violent crime, property crime, drug crime, and more."
        />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="Crime Categories" />
        <meta
            property="og:description"
            content="Browse crime news by category. Violent crime, property crime, drug crime, and more."
        />
        <meta property="og:image" content="/logo.png" />
    </Head>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Back Link -->
        <Link
            href="/"
            class="mb-6 inline-flex items-center text-sm text-public-text-secondary hover:text-public-accent"
        >
            <ChevronLeft class="mr-1 size-4" />
            Back to Home
        </Link>

        <!-- Page Header -->
        <header class="mb-8">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-12 items-center justify-center rounded-lg bg-public-accent/10"
                >
                    <TagIcon class="size-6 text-public-accent" />
                </div>
                <div>
                    <h1
                        class="font-heading text-3xl font-bold text-public-text"
                    >
                        Crime Categories
                    </h1>
                    <p class="text-public-text-muted">
                        Browse news by category
                    </p>
                </div>
            </div>
        </header>

        <!-- Categories Grid -->
        <section>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="category in crimeCategories"
                    :key="category.id"
                    :href="show.url(category)"
                >
                    <Card
                        class="h-full border-public-border bg-public-surface transition-all hover:shadow-md"
                    >
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'size-3 rounded-full',
                                            getTagDotColor(category.color),
                                        ]"
                                    />
                                    <CardTitle class="text-lg text-public-text">
                                        {{ category.name }}
                                    </CardTitle>
                                </div>
                                <Badge
                                    :class="getTagBadgeColor(category.color)"
                                >
                                    {{ category.article_count }} articles
                                </Badge>
                            </div>
                            <p
                                v-if="category.description"
                                class="mt-2 text-sm text-public-text-secondary"
                            >
                                {{ category.description }}
                            </p>
                        </CardHeader>
                    </Card>
                </Link>
            </div>

            <div
                v-if="!crimeCategories.length"
                class="rounded-lg bg-public-bg-subtle py-12 text-center text-public-text-muted"
            >
                No categories found.
            </div>
        </section>
    </main>
</template>

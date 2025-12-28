<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { Article } from '@/types'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Calendar } from 'lucide-vue-next'
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue'

interface Props {
    article: Article
}

const props = defineProps<Props>()

const formattedDate = new Date(props.article.published_at).toLocaleDateString('en-CA', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
})
</script>

<template>
    <Link :href="`/articles/${article.id}`">
        <Card class="h-full transition-shadow hover:shadow-lg">
            <img
                v-if="article.image_url"
                :src="article.image_url"
                :alt="article.title"
                class="h-48 w-full object-cover"
            />

            <CardHeader>
                <div class="mb-2 flex items-center justify-between text-xs">
                    <SourceCredibilityBadge :source="article.news_source" />
                    <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                        <Calendar class="size-3" />
                        {{ formattedDate }}
                    </div>
                </div>

                <CardTitle class="line-clamp-2">
                    {{ article.title }}
                </CardTitle>
            </CardHeader>

            <CardContent>
                <p
                    v-if="article.excerpt"
                    class="mb-4 line-clamp-3 text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ article.excerpt }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="tag in article.tags.slice(0, 3)"
                        :key="tag.id"
                        variant="secondary"
                        class="text-xs"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>

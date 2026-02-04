<script setup lang="ts">
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Article } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';

interface Props {
    article: Article;
}

const props = defineProps<Props>();

const formattedDate = props.article.published_at
    ? new Date(props.article.published_at).toLocaleDateString('en-CA', {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
      })
    : '';
</script>

<template>
    <Link :href="`/articles/${article.id}`">
        <Card
            class="h-full border-zinc-700 bg-zinc-800/50 transition-all hover:bg-zinc-800 hover:shadow-lg"
        >
            <img
                v-if="article.image_url"
                :src="article.image_url"
                :alt="article.title"
                class="h-48 w-full object-cover"
            />

            <CardHeader>
                <div class="mb-2 flex items-center justify-between text-xs">
                    <SourceCredibilityBadge
                        v-if="article.news_source"
                        :source="article.news_source"
                    />
                    <div class="flex items-center gap-1 text-zinc-400">
                        <Calendar class="size-3" />
                        {{ formattedDate }}
                    </div>
                </div>

                <CardTitle class="line-clamp-2 text-white">
                    {{ article.title }}
                </CardTitle>
            </CardHeader>

            <CardContent>
                <p
                    v-if="article.excerpt"
                    class="mb-4 line-clamp-3 text-sm text-zinc-400"
                >
                    {{ article.excerpt }}
                </p>

                <div v-if="article.tags?.length" class="flex flex-wrap gap-2">
                    <Badge
                        v-for="tag in article.tags.slice(0, 3)"
                        :key="tag.id"
                        variant="secondary"
                        class="bg-zinc-700 text-xs text-zinc-300"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>

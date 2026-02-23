<script setup lang="ts">
import ArticleImage from '@/components/ArticleImage.vue';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
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
        <article
            class="group h-full overflow-hidden rounded-lg border border-public-border bg-public-surface transition-all hover:shadow-md"
        >
            <ArticleImage
                v-if="article.image_url"
                :src="article.image_url"
                :alt="article.title"
                container-class="h-48 w-full"
                img-class="h-full w-full"
            />

            <div class="p-4">
                <div class="mb-2 flex items-center justify-between text-xs">
                    <SourceCredibilityBadge
                        v-if="article.news_source"
                        :source="article.news_source"
                    />
                    <div class="flex items-center gap-1 text-public-text-muted">
                        <Calendar class="size-3" aria-hidden="true" />
                        {{ formattedDate }}
                    </div>
                </div>

                <h3
                    class="mb-2 line-clamp-2 font-heading text-lg leading-snug font-semibold text-public-text group-hover:text-public-accent"
                >
                    {{ article.title }}
                </h3>

                <p
                    v-if="article.excerpt"
                    class="mb-4 line-clamp-3 text-sm text-public-text-secondary"
                >
                    {{ article.excerpt }}
                </p>

                <div v-if="article.tags?.length" class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="tag in article.tags.slice(0, 3)"
                        :key="tag.id"
                        variant="secondary"
                        class="bg-public-bg-subtle text-xs text-public-text-secondary"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </div>
        </article>
    </Link>
</template>

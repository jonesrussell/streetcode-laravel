<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticleImage from '@/components/ArticleImage.vue';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Article } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, ChevronLeft, ExternalLink, User } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

interface Props {
    article: Article;
    relatedArticles: Article[];
    canonicalUrl: string;
    ogImage: string;
}

const props = defineProps<Props>();

const formattedDate = props.article.published_at
    ? new Date(props.article.published_at).toLocaleDateString('en-CA', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
      })
    : 'Not published';

const description =
    props.article.excerpt || props.article.metadata?.og_description || '';
</script>

<template>
    <Head :title="article.title">
        <meta
            name="description"
            head-key="description"
            :content="description"
        />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:type" content="article" />
        <meta property="og:title" :content="article.title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta name="twitter:title" :content="article.title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImage" />
    </Head>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <Link
                href="/"
                class="inline-flex items-center gap-1 text-sm text-public-text-muted hover:text-public-accent"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
                Back to Home
            </Link>
        </div>

        <!-- Article Header -->
        <article>
            <header class="mb-8">
                <h1
                    class="mb-4 font-heading text-4xl leading-tight font-bold text-public-text"
                >
                    {{ article.title }}
                </h1>

                <!-- Meta Info -->
                <div
                    class="flex flex-wrap items-center gap-4 text-sm text-public-text-secondary"
                >
                    <div class="flex items-center gap-2">
                        <Calendar class="size-4" aria-hidden="true" />
                        {{ formattedDate }}
                    </div>

                    <div v-if="article.author" class="flex items-center gap-2">
                        <User class="size-4" aria-hidden="true" />
                        {{ article.author }}
                    </div>

                    <SourceCredibilityBadge
                        v-if="article.news_source"
                        :source="article.news_source"
                    />
                </div>

                <!-- Tags -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <Badge
                        v-for="tag in article.tags"
                        :key="tag.id"
                        :variant="
                            tag.type === 'crime_category'
                                ? 'default'
                                : 'secondary'
                        "
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </header>

            <!-- Featured Image -->
            <ArticleImage
                v-if="article.image_url"
                :src="article.image_url"
                :alt="article.title"
                container-class="mb-8 w-full rounded-lg"
                img-class="w-full rounded-lg"
            />

            <!-- Description -->
            <p v-if="description" class="text-lg text-public-text-secondary">
                {{ description }}
            </p>

            <!-- Read Full Article Link -->
            <a
                :href="article.url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-6 inline-flex items-center gap-2 text-public-accent hover:text-public-accent-hover"
            >
                Read full article at
                {{ article.news_source?.name ?? 'source' }}
                <ExternalLink class="size-4" aria-hidden="true" />
            </a>
        </article>

        <!-- Related Articles -->
        <section v-if="relatedArticles.length" class="mt-16">
            <h2 class="mb-6 font-heading text-2xl font-bold text-public-text">
                Related Articles
            </h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <ArticleCard
                    v-for="relatedArticle in relatedArticles"
                    :key="relatedArticle.id"
                    :article="relatedArticle"
                />
            </div>
        </section>
    </main>
</template>

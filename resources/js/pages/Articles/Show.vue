<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import ArticleImage from '@/components/ArticleImage.vue';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Article } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronLeft,
    Clock,
    ExternalLink,
    MapPin,
    User,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

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

const isoDate = props.article.published_at
    ? new Date(props.article.published_at).toISOString()
    : null;

const description =
    props.article.excerpt || props.article.metadata?.og_description || '';

const readTimeMinutes = computed(() => {
    const wordCount = props.article.metadata?.word_count;
    if (!wordCount || wordCount <= 0) {
        return null;
    }
    return Math.max(1, Math.ceil(wordCount / 200));
});

const locationLabel = computed(() => {
    const city = props.article.city;
    if (city) {
        return `${city.city_name}, ${city.region_code}`;
    }
    const meta = props.article.metadata;
    if (meta?.location_city && meta?.location_province) {
        const cityName = meta.location_city
            .split('-')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ');
        return `${cityName}, ${meta.location_province}`;
    }
    return null;
});

const cityUrl = computed(() => props.article.city?.url_path ?? null);

const structuredData = computed(() => {
    const data: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'NewsArticle',
        headline: props.article.title,
        description: description,
        image: props.ogImage,
        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': props.canonicalUrl,
        },
        isAccessibleForFree: false,
        hasPart: {
            '@type': 'WebPageElement',
            isAccessibleForFree: true,
            cssSelector: '.article-preview',
        },
    };

    if (isoDate) {
        data.datePublished = isoDate;
    }

    if (props.article.author) {
        data.author = {
            '@type': 'Person',
            name: props.article.author,
        };
    }

    if (props.article.news_source) {
        data.publisher = {
            '@type': 'Organization',
            name: props.article.news_source.name,
            url: props.article.news_source.url,
        };
        if (props.article.news_source.logo_url) {
            (data.publisher as Record<string, unknown>).logo = {
                '@type': 'ImageObject',
                url: props.article.news_source.logo_url,
            };
        }
    }

    return JSON.stringify(data);
});

let structuredDataScript: HTMLScriptElement | null = null;

onMounted(() => {
    structuredDataScript = document.createElement('script');
    structuredDataScript.type = 'application/ld+json';
    structuredDataScript.textContent = structuredData.value;
    structuredDataScript.id = 'article-structured-data';
    document.head.appendChild(structuredDataScript);
});

onUnmounted(() => {
    if (structuredDataScript && structuredDataScript.parentNode) {
        structuredDataScript.parentNode.removeChild(structuredDataScript);
    }
});
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
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-public-text-secondary"
                >
                    <div class="flex items-center gap-2">
                        <Calendar class="size-4" aria-hidden="true" />
                        {{ formattedDate }}
                    </div>

                    <div v-if="article.author" class="flex items-center gap-2">
                        <User class="size-4" aria-hidden="true" />
                        {{ article.author }}
                    </div>

                    <div v-if="readTimeMinutes" class="flex items-center gap-2">
                        <Clock class="size-4" aria-hidden="true" />
                        {{ readTimeMinutes }} min read
                    </div>

                    <Link
                        v-if="locationLabel && cityUrl"
                        :href="cityUrl"
                        class="flex items-center gap-2 hover:text-public-accent"
                    >
                        <MapPin class="size-4" aria-hidden="true" />
                        {{ locationLabel }}
                    </Link>
                    <div
                        v-else-if="locationLabel"
                        class="flex items-center gap-2"
                    >
                        <MapPin class="size-4" aria-hidden="true" />
                        {{ locationLabel }}
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

            <!-- Article Preview -->
            <div
                v-if="description"
                class="article-preview rounded-lg border border-public-border bg-public-surface p-6"
            >
                <h2
                    class="mb-3 text-sm font-semibold tracking-wide text-public-text-muted uppercase"
                >
                    Article Preview
                </h2>
                <p class="text-lg leading-relaxed text-public-text-secondary">
                    {{ description }}
                </p>
                <p class="mt-4 text-xs text-public-text-muted">
                    Preview provided under fair use. Full article available at
                    original source.
                </p>
            </div>

            <!-- Read Full Article CTA -->
            <div class="mt-6">
                <Button as="a" :href="article.url" target="_blank" size="lg">
                    Read full article at
                    {{ article.news_source?.name ?? 'source' }}
                    <ExternalLink class="size-4" aria-hidden="true" />
                </Button>
                <p
                    v-if="readTimeMinutes"
                    class="mt-2 text-sm text-public-text-muted"
                >
                    Estimated reading time: {{ readTimeMinutes }} minute{{
                        readTimeMinutes > 1 ? 's' : ''
                    }}
                </p>
            </div>
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

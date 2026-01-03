<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import type { NewsSource, Tag, BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import TagMultiSelect from '@/components/admin/TagMultiSelect.vue';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    newsSources: NewsSource[];
    tags: Tag[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Articles', href: route('admin.articles.index') },
    { title: 'Create', href: route('admin.articles.create') },
];

const form = ref({
    title: '',
    url: '',
    excerpt: '',
    content: '',
    image_url: '',
    author: '',
    news_source_id: props.newsSources[0]?.id || null,
    tags: [] as number[],
    is_featured: false,
    published_at: null as string | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleSubmit = (publish: boolean = false) => {
    processing.value = true;
    errors.value = {};

    const data = {
        ...form.value,
        published_at: publish ? new Date().toISOString() : null,
    };

    router.post(route('admin.articles.store'), data, {
        preserveScroll: true,
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleCancel = () => {
    router.get(route('admin.articles.index'));
};
</script>

<template>
    <Head title="Create Article - Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="handleCancel"
                        class="mb-2"
                    >
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Articles
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">Create Article</h1>
                    <p class="text-muted-foreground mt-1">
                        Add a new article to your collection
                    </p>
                </div>
            </div>

            <!-- Form -->
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="handleSubmit(false)" class="space-y-6">
                        <!-- Title -->
                        <div class="space-y-2">
                            <Label for="title">
                                Title
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Enter article title"
                                :class="{ 'border-destructive': errors.title }"
                            />
                            <p v-if="errors.title" class="text-sm text-destructive">
                                {{ errors.title }}
                            </p>
                        </div>

                        <!-- URL -->
                        <div class="space-y-2">
                            <Label for="url">
                                URL
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="url"
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.com/article"
                                :class="{ 'border-destructive': errors.url }"
                            />
                            <p v-if="errors.url" class="text-sm text-destructive">
                                {{ errors.url }}
                            </p>
                        </div>

                        <!-- Excerpt -->
                        <div class="space-y-2">
                            <Label for="excerpt">Excerpt</Label>
                            <textarea
                                id="excerpt"
                                v-model="form.excerpt"
                                rows="3"
                                placeholder="Short summary of the article..."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': errors.excerpt }"
                            ></textarea>
                            <p v-if="errors.excerpt" class="text-sm text-destructive">
                                {{ errors.excerpt }}
                            </p>
                        </div>

                        <!-- Content -->
                        <div class="space-y-2">
                            <Label for="content">
                                Content
                                <span class="text-destructive">*</span>
                            </Label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                rows="10"
                                placeholder="Full article content..."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': errors.content }"
                            ></textarea>
                            <p v-if="errors.content" class="text-sm text-destructive">
                                {{ errors.content }}
                            </p>
                        </div>

                        <!-- Image URL -->
                        <div class="space-y-2">
                            <Label for="image_url">Image URL</Label>
                            <Input
                                id="image_url"
                                v-model="form.image_url"
                                type="url"
                                placeholder="https://example.com/image.jpg"
                                :class="{ 'border-destructive': errors.image_url }"
                            />
                            <p v-if="errors.image_url" class="text-sm text-destructive">
                                {{ errors.image_url }}
                            </p>
                        </div>

                        <!-- Author -->
                        <div class="space-y-2">
                            <Label for="author">Author</Label>
                            <Input
                                id="author"
                                v-model="form.author"
                                type="text"
                                placeholder="Article author name"
                                :class="{ 'border-destructive': errors.author }"
                            />
                            <p v-if="errors.author" class="text-sm text-destructive">
                                {{ errors.author }}
                            </p>
                        </div>

                        <!-- News Source -->
                        <div class="space-y-2">
                            <Label for="news_source_id">
                                News Source
                                <span class="text-destructive">*</span>
                            </Label>
                            <select
                                id="news_source_id"
                                v-model="form.news_source_id"
                                class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': errors.news_source_id }"
                            >
                                <option
                                    v-for="source in newsSources"
                                    :key="source.id"
                                    :value="source.id"
                                >
                                    {{ source.name }}
                                </option>
                            </select>
                            <p v-if="errors.news_source_id" class="text-sm text-destructive">
                                {{ errors.news_source_id }}
                            </p>
                        </div>

                        <!-- Tags -->
                        <div class="space-y-2">
                            <Label>Tags</Label>
                            <TagMultiSelect
                                v-model="form.tags"
                                :tags="tags"
                            />
                            <p v-if="errors.tags" class="text-sm text-destructive">
                                {{ errors.tags }}
                            </p>
                        </div>

                        <!-- Featured -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_featured"
                                v-model:checked="form.is_featured"
                            />
                            <Label for="is_featured" class="cursor-pointer">
                                Featured article
                            </Label>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                @click="handleCancel"
                                :disabled="processing"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                            >
                                {{ processing ? 'Saving...' : 'Save as Draft' }}
                            </Button>
                            <Button
                                type="button"
                                @click="handleSubmit(true)"
                                :disabled="processing"
                            >
                                {{ processing ? 'Publishing...' : 'Publish' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

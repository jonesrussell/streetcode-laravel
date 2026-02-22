<script setup lang="ts">
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import TagMultiSelect from '@/components/admin/TagMultiSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    destroy as articlesDestroy,
    edit as articlesEdit,
    index as articlesIndex,
    update as articlesUpdate,
} from '@/routes/dashboard/articles';
import type { Article, BreadcrumbItem, NewsSource, Tag } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    article: Article;
    newsSources: NewsSource[];
    tags: Tag[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Articles', href: articlesIndex().url },
    { title: 'Edit', href: articlesEdit(props.article.id).url },
];

const form = ref({
    title: props.article.title,
    url: props.article.url,
    excerpt: props.article.excerpt || '',
    content: props.article.content || '',
    image_url: props.article.image_url || '',
    author: props.article.author || '',
    news_source_id: props.article.news_source_id,
    tags: props.article.tags?.map((t) => t.id) || [],
    is_featured: props.article.is_featured,
    published_at: props.article.published_at,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const deleteDialogOpen = ref(false);
const isDeleting = ref(false);

const isPublished = computed(() => form.value.published_at !== null);

const handleSubmit = (publish: boolean = false) => {
    processing.value = true;
    errors.value = {};

    const data = {
        ...form.value,
        published_at: publish
            ? form.value.published_at || new Date().toISOString()
            : isPublished.value
              ? form.value.published_at
              : null,
    };

    router.patch(articlesUpdate(props.article.id).url, data, {
        preserveScroll: true,
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleUnpublish = () => {
    processing.value = true;
    errors.value = {};

    router.patch(
        articlesUpdate(props.article.id).url,
        { ...form.value, published_at: null },
        {
            preserveScroll: true,
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                processing.value = false;
                form.value.published_at = null;
            },
        },
    );
};

const handleDeleteClick = () => {
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    router.delete(articlesDestroy(props.article.id).url, {
        onSuccess: () => {
            router.get(articlesIndex().url);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const handleCancel = () => {
    router.get(articlesIndex().url);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Edit: ${article.title} - Dashboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="handleCancel"
                        class="mb-2"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Articles
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Edit Article
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Update article details
                    </p>
                </div>
                <Button variant="destructive" @click="handleDeleteClick">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Article
                </Button>
            </div>

            <!-- Metadata Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Article Metadata</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">
                                {{ formatDate(article.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Last Updated</p>
                            <p class="font-medium">
                                {{ formatDate(article.updated_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Views</p>
                            <p class="font-medium">
                                {{ article.view_count.toLocaleString() }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Status</p>
                            <Badge
                                :variant="isPublished ? 'default' : 'secondary'"
                            >
                                {{ isPublished ? 'Published' : 'Draft' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form -->
            <Card>
                <CardContent class="pt-6">
                    <form
                        @submit.prevent="handleSubmit(false)"
                        class="space-y-6"
                    >
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
                            <p
                                v-if="errors.title"
                                class="text-sm text-destructive"
                            >
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
                            <p
                                v-if="errors.url"
                                class="text-sm text-destructive"
                            >
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
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{
                                    'border-destructive': errors.excerpt,
                                }"
                            ></textarea>
                            <p
                                v-if="errors.excerpt"
                                class="text-sm text-destructive"
                            >
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
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{
                                    'border-destructive': errors.content,
                                }"
                            ></textarea>
                            <p
                                v-if="errors.content"
                                class="text-sm text-destructive"
                            >
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
                                :class="{
                                    'border-destructive': errors.image_url,
                                }"
                            />
                            <p
                                v-if="errors.image_url"
                                class="text-sm text-destructive"
                            >
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
                            <p
                                v-if="errors.author"
                                class="text-sm text-destructive"
                            >
                                {{ errors.author }}
                            </p>
                        </div>

                        <!-- News Source -->
                        <div class="space-y-2">
                            <Label for="news_source_id">
                                News Source
                                <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                v-model="form.news_source_id"
                                :class="{
                                    'border-destructive': errors.news_source_id,
                                }"
                            >
                                <SelectTrigger
                                    id="news_source_id"
                                    :class="{
                                        'border-destructive':
                                            errors.news_source_id,
                                    }"
                                >
                                    <SelectValue
                                        placeholder="Select a news source"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="source in newsSources"
                                        :key="source.id"
                                        :value="source.id"
                                    >
                                        {{ source.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="errors.news_source_id"
                                class="text-sm text-destructive"
                            >
                                {{ errors.news_source_id }}
                            </p>
                        </div>

                        <!-- Tags -->
                        <div class="space-y-2">
                            <Label>Tags</Label>
                            <TagMultiSelect
                                v-model="form.tags"
                                :options="tags"
                            />
                            <p
                                v-if="errors.tags"
                                class="text-sm text-destructive"
                            >
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
                        <div class="flex gap-3 border-t pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                @click="handleCancel"
                                :disabled="processing"
                            >
                                Cancel
                            </Button>
                            <Button
                                v-if="isPublished"
                                type="button"
                                variant="outline"
                                @click="handleUnpublish"
                                :disabled="processing"
                            >
                                {{
                                    processing ? 'Unpublishing...' : 'Unpublish'
                                }}
                            </Button>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                            >
                                {{ processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                            <Button
                                v-if="!isPublished"
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

        <!-- Delete Confirmation Dialog -->
        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete Article"
            :description="`Are you sure you want to delete &quot;${article.title}&quot;? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="() => (deleteDialogOpen = false)"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import type { Tag } from '@/types';
import { X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    tags: Tag[];
    modelValue: number[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const searchQuery = ref('');
const isOpen = ref(false);

const selectedTags = computed(() =>
    props.tags.filter((tag) => props.modelValue.includes(tag.id)),
);

const filteredTags = computed(() =>
    props.tags.filter((tag) =>
        tag.name.toLowerCase().includes(searchQuery.value.toLowerCase()),
    ),
);

const toggleTag = (tagId: number) => {
    const newValue = props.modelValue.includes(tagId)
        ? props.modelValue.filter((id) => id !== tagId)
        : [...props.modelValue, tagId];

    emit('update:modelValue', newValue);
};

const removeTag = (tagId: number) => {
    emit(
        'update:modelValue',
        props.modelValue.filter((id) => id !== tagId),
    );
};

const handleInputBlur = () => {
    window.setTimeout(() => {
        isOpen.value = false;
    }, 200);
};
</script>

<template>
    <div class="space-y-2">
        <div class="mb-2 flex flex-wrap gap-2" v-if="selectedTags.length > 0">
            <Badge
                v-for="tag in selectedTags"
                :key="tag.id"
                variant="secondary"
                class="flex items-center gap-1"
            >
                {{ tag.name }}
                <button
                    type="button"
                    @click="removeTag(tag.id)"
                    class="rounded-full p-0.5 hover:bg-muted"
                >
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>

        <div class="relative">
            <Input
                v-model="searchQuery"
                type="text"
                placeholder="Search tags..."
                @focus="isOpen = true"
                @blur="handleInputBlur"
            />

            <div
                v-if="isOpen && filteredTags.length > 0"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-background shadow-lg"
            >
                <div
                    v-for="tag in filteredTags"
                    :key="tag.id"
                    class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                    @mousedown.prevent="toggleTag(tag.id)"
                >
                    <Checkbox
                        :checked="modelValue.includes(tag.id)"
                        @click.stop="toggleTag(tag.id)"
                    />
                    <span class="text-sm">{{ tag.name }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

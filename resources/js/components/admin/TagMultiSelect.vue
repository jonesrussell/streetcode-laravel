<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Tag } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { X } from 'lucide-vue-next';

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
    props.tags.filter(tag => props.modelValue.includes(tag.id))
);

const filteredTags = computed(() =>
    props.tags.filter(tag =>
        tag.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
);

const toggleTag = (tagId: number) => {
    const newValue = props.modelValue.includes(tagId)
        ? props.modelValue.filter(id => id !== tagId)
        : [...props.modelValue, tagId];

    emit('update:modelValue', newValue);
};

const removeTag = (tagId: number) => {
    emit('update:modelValue', props.modelValue.filter(id => id !== tagId));
};

const handleInputBlur = () => {
    window.setTimeout(() => {
        isOpen.value = false;
    }, 200);
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-2 mb-2" v-if="selectedTags.length > 0">
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
                    class="hover:bg-muted rounded-full p-0.5"
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
                class="absolute z-10 w-full mt-1 bg-background border rounded-md shadow-lg max-h-60 overflow-auto"
            >
                <div
                    v-for="tag in filteredTags"
                    :key="tag.id"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-muted cursor-pointer"
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

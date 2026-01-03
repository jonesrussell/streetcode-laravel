<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

interface Props {
    publishedAt: string | null;
}

const props = defineProps<Props>();
</script>

<template>
    <Badge :variant="publishedAt ? 'default' : 'secondary'">
        {{ publishedAt ? 'Published' : 'Draft' }}
    </Badge>
</template>

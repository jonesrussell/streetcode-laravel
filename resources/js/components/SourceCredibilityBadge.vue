<script setup lang="ts">
import type { NewsSource } from '@/types'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'

interface Props {
    source: NewsSource
}

const props = defineProps<Props>()

const biasColorClass = {
    left: 'bg-blue-500',
    'center-left': 'bg-sky-500',
    center: 'bg-gray-500',
    'center-right': 'bg-orange-500',
    right: 'bg-red-500',
}[props.source.bias_rating || 'center'] || 'bg-gray-500'

const credibilityLevel = props.source.credibility_score
    ? props.source.credibility_score >= 80
        ? 'High'
        : props.source.credibility_score >= 60
          ? 'Medium'
          : 'Low'
    : 'Unknown'
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <div class="flex items-center gap-2">
                    <div :class="['size-2 rounded-full', biasColorClass]" />
                    <span class="text-xs font-medium">{{ source.name }}</span>
                </div>
            </TooltipTrigger>
            <TooltipContent>
                <div class="space-y-1 text-xs">
                    <div>Bias: {{ source.bias_rating || 'Unknown' }}</div>
                    <div>
                        Credibility: {{ credibilityLevel }}
                        {{ source.credibility_score ? `(${source.credibility_score})` : '' }}
                    </div>
                    <div v-if="source.factual_reporting_score">
                        Factual: {{ source.factual_reporting_score }}
                    </div>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>

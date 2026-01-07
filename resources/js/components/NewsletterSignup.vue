<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Mail, Check } from 'lucide-vue-next';

const email = ref('');
const isSubmitted = ref(false);
const isLoading = ref(false);

const handleSubmit = () => {
    if (!email.value) {
        return;
    }
    isLoading.value = true;

    // Simulate submission - replace with actual API call
    setTimeout(() => {
        isSubmitted.value = true;
        isLoading.value = false;
    }, 500);
};
</script>

<template>
    <div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">
        <div class="mb-3 flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-full bg-red-500/20">
                <Mail class="size-4 text-red-400" />
            </div>
            <h3 class="font-semibold text-white">Daily Digest</h3>
        </div>

        <p class="mb-4 text-sm text-zinc-400">
            Get the daily StreetCode report sent to your inbox and stay up to date with your bias blindspot.
        </p>

        <div v-if="!isSubmitted">
            <form @submit.prevent="handleSubmit" class="space-y-3">
                <Input
                    v-model="email"
                    type="email"
                    placeholder="Email address"
                    class="border-zinc-600 bg-zinc-900 text-white placeholder:text-zinc-500"
                    required
                />
                <Button
                    type="submit"
                    class="w-full bg-red-600 hover:bg-red-700"
                    :disabled="isLoading"
                >
                    {{ isLoading ? 'Subscribing...' : 'Subscribe' }}
                </Button>
            </form>
        </div>

        <div v-else class="flex items-center gap-2 rounded-lg bg-green-500/20 p-3 text-green-400">
            <Check class="size-5" />
            <span class="text-sm">Thanks for subscribing!</span>
        </div>
    </div>
</template>

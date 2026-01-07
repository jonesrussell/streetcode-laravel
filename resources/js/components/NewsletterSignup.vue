<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Mail, Check, AlertCircle } from 'lucide-vue-next';
import { store } from '@/actions/App/Http/Controllers/SubscriberController';

const email = ref('');
const isSubmitted = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');

const handleSubmit = async () => {
    if (!email.value) {
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(store.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find(row => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] || ''
                ),
            },
            body: JSON.stringify({ email: email.value }),
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors?.email) {
                errorMessage.value = data.errors.email[0];
            } else if (data.message) {
                errorMessage.value = data.message;
            } else {
                errorMessage.value = 'Something went wrong. Please try again.';
            }
            return;
        }

        isSubmitted.value = true;
    } catch {
        errorMessage.value = 'Unable to connect. Please try again.';
    } finally {
        isLoading.value = false;
    }
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

                <div v-if="errorMessage" class="flex items-center gap-2 rounded-lg bg-red-500/20 p-2 text-red-400">
                    <AlertCircle class="size-4 shrink-0" />
                    <span class="text-sm">{{ errorMessage }}</span>
                </div>

                <Button
                    type="submit"
                    class="w-full bg-red-600 hover:bg-red-700"
                    :disabled="isLoading"
                >
                    {{ isLoading ? 'Subscribing...' : 'Subscribe' }}
                </Button>
            </form>
        </div>

        <div v-else class="space-y-2">
            <div class="flex items-center gap-2 rounded-lg bg-green-500/20 p-3 text-green-400">
                <Check class="size-5" />
                <span class="text-sm">Check your email to verify!</span>
            </div>
            <p class="text-xs text-zinc-500">
                We've sent a verification link to your email address.
            </p>
        </div>
    </div>
</template>

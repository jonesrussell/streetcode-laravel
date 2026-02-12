<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/SubscriberController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AlertCircle, Check, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

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
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] || '',
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
    <div class="rounded-lg border border-public-border bg-public-surface p-4">
        <div class="mb-3 flex items-center gap-2">
            <div
                class="flex size-8 items-center justify-center rounded-full bg-public-accent-subtle"
            >
                <Mail class="size-4 text-public-accent" />
            </div>
            <h3 class="font-heading font-semibold text-public-text">
                Daily Digest
            </h3>
        </div>

        <p class="mb-4 text-sm text-public-text-secondary">
            Get the daily StreetCode report sent to your inbox and stay up to
            date with your bias blindspot.
        </p>

        <div v-if="!isSubmitted">
            <form @submit.prevent="handleSubmit" class="space-y-3">
                <Input
                    v-model="email"
                    type="email"
                    placeholder="Email address"
                    class="border-public-border bg-public-bg text-public-text placeholder:text-public-text-muted"
                    required
                />

                <div
                    v-if="errorMessage"
                    class="flex items-center gap-2 rounded-lg bg-red-500/10 p-2 text-red-600 dark:text-red-400"
                >
                    <AlertCircle class="size-4 shrink-0" />
                    <span class="text-sm">{{ errorMessage }}</span>
                </div>

                <Button
                    type="submit"
                    class="w-full bg-public-accent-button text-white hover:bg-public-accent"
                    :disabled="isLoading"
                >
                    {{ isLoading ? 'Subscribing...' : 'Subscribe' }}
                </Button>
            </form>
        </div>

        <div v-else class="space-y-2">
            <div
                class="flex items-center gap-2 rounded-lg bg-green-500/10 p-3 text-green-600 dark:text-green-400"
            >
                <Check class="size-5" />
                <span class="text-sm">Check your email to verify!</span>
            </div>
            <p class="text-xs text-public-text-muted">
                We've sent a verification link to your email address.
            </p>
        </div>
    </div>
</template>

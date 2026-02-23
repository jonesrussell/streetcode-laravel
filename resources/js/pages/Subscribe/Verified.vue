<script setup lang="ts">
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Mail } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });

defineProps<{
    alreadyVerified: boolean;
}>();
</script>

<template>
    <Head title="Subscription Verified" />

    <div class="flex min-h-[60vh] items-center justify-center px-4 py-16">
        <div
            class="w-full max-w-md rounded-lg border border-public-border bg-public-surface p-8 text-center"
        >
            <div
                class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-green-500/10"
            >
                <CheckCircle
                    class="size-8 text-green-600 dark:text-green-400"
                />
            </div>

            <h1 class="mb-2 font-heading text-2xl font-bold text-public-text">
                {{
                    alreadyVerified
                        ? 'Already Verified'
                        : 'Subscription Verified!'
                }}
            </h1>

            <p class="mb-6 text-public-text-secondary">
                <template v-if="alreadyVerified">
                    Your email address has already been verified. You're all set
                    to receive our Daily Digest.
                </template>
                <template v-else>
                    Thank you for verifying your email address. You'll now
                    receive our Daily Digest with the latest crime news updates.
                </template>
            </p>

            <div
                class="flex items-center justify-center gap-2 rounded-lg bg-public-bg-subtle p-4 text-public-text-secondary"
            >
                <Mail class="size-5 text-public-accent" />
                <span class="text-sm">Daily Digest subscription active</span>
            </div>

            <Link href="/">
                <Button
                    class="mt-6 w-full bg-public-accent text-white hover:bg-public-accent-hover"
                >
                    Go to Homepage
                </Button>
            </Link>
        </div>
    </div>
</template>

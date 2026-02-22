<script setup lang="ts">
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { MailX } from 'lucide-vue-next';

defineOptions({ layout: PublicLayout });
</script>

<template>
    <Head title="Unsubscribed" />

    <div class="flex min-h-[60vh] items-center justify-center px-4 py-16">
        <div
            class="w-full max-w-md rounded-lg border border-public-border bg-public-surface p-8 text-center"
        >
            <div
                class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-public-bg-subtle"
            >
                <MailX class="size-8 text-public-text-muted" />
            </div>

            <h1 class="mb-2 font-heading text-2xl font-bold text-public-text">
                You've Been Unsubscribed
            </h1>

            <p class="mb-6 text-public-text-secondary">
                You've been successfully removed from our Daily Digest mailing
                list. We're sorry to see you go!
            </p>

            <p class="mb-6 text-sm text-public-text-muted">
                Changed your mind? You can always subscribe again from our
                homepage.
            </p>

            <Link href="/">
                <Button
                    class="w-full bg-public-accent text-white hover:bg-public-accent-hover"
                >
                    Go to Homepage
                </Button>
            </Link>
        </div>
    </div>
</template>

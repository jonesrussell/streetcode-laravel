<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MailX } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
</script>

<template>
    <Head title="Unsubscribed" />

    <div class="flex min-h-screen items-center justify-center bg-zinc-900 px-4">
        <div class="w-full max-w-md rounded-lg border border-zinc-700 bg-zinc-800/50 p-8 text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-zinc-700">
                <MailX class="size-8 text-zinc-400" />
            </div>

            <h1 class="mb-2 text-2xl font-bold text-white">
                You've Been Unsubscribed
            </h1>

            <p class="mb-6 text-zinc-400">
                You've been successfully removed from our Daily Digest mailing list. We're sorry to see you go!
            </p>

            <p class="mb-6 text-sm text-zinc-500">
                Changed your mind? You can always subscribe again from our homepage.
            </p>

            <Link href="/">
                <Button class="w-full bg-red-600 hover:bg-red-700">
                    Go to Homepage
                </Button>
            </Link>
        </div>
    </div>
</template>

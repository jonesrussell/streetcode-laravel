<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Input } from '@/components/ui/input';
import { Link, router } from '@inertiajs/vue3';
import { Menu, Search, X } from 'lucide-vue-next';
import { ref } from 'vue';

const searchQuery = ref('');
const showMobileMenu = ref(false);
const showSearch = ref(false);

const performSearch = () => {
    if (searchQuery.value.trim()) {
        router.get('/', { search: searchQuery.value });
    }
};

const navLinks = [
    { name: 'Home', href: '/' },
    { name: 'United States', href: '/crime/us' },
    { name: 'Canada', href: '/crime/ca' },
    { name: 'United Kingdom', href: '/crime/gb' },
];
</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-public-border bg-public-surface"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <!-- Logo -->
                <Link
                    href="/"
                    class="flex h-14 shrink-0 items-center p-2 hover:opacity-90"
                >
                    <AppLogoIcon class="max-h-full w-auto object-contain" />
                </Link>

                <!-- Desktop Navigation -->
                <nav class="hidden items-center gap-1 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-md px-3 py-1.5 text-sm font-medium text-public-text-secondary transition-colors hover:bg-public-accent-subtle hover:text-public-accent"
                    >
                        {{ link.name }}
                    </Link>
                </nav>

                <!-- Search + Mobile Toggle -->
                <div class="flex items-center gap-2">
                    <!-- Desktop search -->
                    <div class="relative hidden md:block">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-public-text-muted"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search articles..."
                            class="w-56 border-public-border bg-public-bg pl-10 text-public-text placeholder:text-public-text-muted"
                            @keyup.enter="performSearch"
                        />
                    </div>

                    <!-- Mobile search toggle -->
                    <button
                        class="rounded-md p-2 text-public-text-secondary hover:bg-public-bg-subtle md:hidden"
                        @click="showSearch = !showSearch"
                    >
                        <Search class="size-5" />
                    </button>

                    <!-- Mobile menu toggle -->
                    <button
                        class="rounded-md p-2 text-public-text-secondary hover:bg-public-bg-subtle md:hidden"
                        @click="showMobileMenu = !showMobileMenu"
                    >
                        <X v-if="showMobileMenu" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Search -->
            <div v-if="showSearch" class="border-t border-public-border py-3 md:hidden">
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-public-text-muted"
                    />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search articles..."
                        class="w-full border-public-border bg-public-bg pl-10 text-public-text placeholder:text-public-text-muted"
                        @keyup.enter="performSearch"
                    />
                </div>
            </div>

            <!-- Mobile Menu -->
            <div
                v-if="showMobileMenu"
                class="border-t border-public-border py-3 md:hidden"
            >
                <nav class="flex flex-col gap-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-md px-3 py-2 text-sm font-medium text-public-text-secondary hover:bg-public-bg-subtle hover:text-public-accent"
                        @click="showMobileMenu = false"
                    >
                        {{ link.name }}
                    </Link>
                </nav>
            </div>
        </div>
    </header>
</template>

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
                    aria-label="StreetCode home"
                    class="flex h-14 shrink-0 items-center p-2 hover:opacity-90"
                >
                    <AppLogoIcon class="max-h-full w-auto object-contain" />
                </Link>

                <!-- Desktop Navigation -->
                <nav
                    class="hidden items-center gap-1 md:flex"
                    aria-label="Main navigation"
                >
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
                    <form
                        role="search"
                        aria-label="Search articles"
                        class="relative hidden md:block"
                        @submit.prevent="performSearch"
                    >
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-public-text-muted"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search articles..."
                            class="w-56 border-public-border bg-public-bg pl-10 text-public-text placeholder:text-public-text-muted"
                            aria-label="Search articles"
                            @keyup.enter="performSearch"
                        />
                    </form>

                    <!-- Mobile search toggle -->
                    <button
                        type="button"
                        class="rounded-md p-2 text-public-text-secondary hover:bg-public-bg-subtle md:hidden"
                        :aria-label="showSearch ? 'Close search' : 'Open search'"
                        :aria-expanded="showSearch"
                        aria-controls="mobile-search"
                        @click="showSearch = !showSearch"
                    >
                        <Search class="size-5" aria-hidden="true" />
                    </button>

                    <!-- Mobile menu toggle -->
                    <button
                        type="button"
                        class="rounded-md p-2 text-public-text-secondary hover:bg-public-bg-subtle md:hidden"
                        :aria-label="showMobileMenu ? 'Close menu' : 'Open menu'"
                        :aria-expanded="showMobileMenu"
                        aria-controls="mobile-menu"
                        @click="showMobileMenu = !showMobileMenu"
                    >
                        <X
                            v-if="showMobileMenu"
                            class="size-5"
                            aria-hidden="true"
                        />
                        <Menu v-else class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <!-- Mobile Search -->
            <div
                v-if="showSearch"
                id="mobile-search"
                class="border-t border-public-border py-3 md:hidden"
            >
                <form
                    role="search"
                    aria-label="Search articles"
                    class="relative"
                    @submit.prevent="performSearch"
                >
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-public-text-muted"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search articles..."
                        class="w-full border-public-border bg-public-bg pl-10 text-public-text placeholder:text-public-text-muted"
                        aria-label="Search articles"
                        @keyup.enter="performSearch"
                    />
                </form>
            </div>

            <!-- Mobile Menu -->
            <div
                v-if="showMobileMenu"
                id="mobile-menu"
                class="border-t border-public-border py-3 md:hidden"
            >
                <nav
                    class="flex flex-col gap-1"
                    aria-label="Main navigation"
                >
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

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface FooterLink {
    name: string;
    href: string;
    external?: boolean;
}

const currentYear = new Date().getFullYear();

const footerLinks: Record<string, { title: string; links: FooterLink[] }> = {
    news: {
        title: 'News',
        links: [
            { name: 'Home', href: '/' },
            { name: 'Latest Stories', href: '/' },
            { name: 'Featured', href: '/?featured=1' },
        ],
    },
    categories: {
        title: 'Categories',
        links: [
            { name: 'Gang Violence', href: '/?tag=gang-violence' },
            { name: 'Organized Crime', href: '/?tag=organized-crime' },
            { name: 'Drug Crime', href: '/?tag=drug-crime' },
            { name: 'Theft', href: '/?tag=theft' },
            { name: 'Assault', href: '/?tag=assault' },
        ],
    },
    company: {
        title: 'Company',
        links: [
            { name: 'About', href: '/about' },
            { name: 'Contact', href: '/contact' },
            { name: 'Privacy Policy', href: '/privacy' },
            { name: 'Terms of Service', href: '/terms' },
        ],
    },
    connect: {
        title: 'Connect',
        links: [
            { name: 'Twitter', href: 'https://twitter.com', external: true },
            { name: 'Facebook', href: 'https://facebook.com', external: true },
            { name: 'RSS Feed', href: '/feed' },
        ],
    },
};
</script>

<template>
    <footer class="border-t border-zinc-800 bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
                <!-- Brand Column -->
                <div class="lg:col-span-1">
                    <Link
                        href="/"
                        class="text-lg font-bold text-white hover:text-zinc-200"
                    >
                        StreetCode
                    </Link>
                    <p class="mt-4 text-sm text-zinc-400">
                        Canadian crime news aggregation. Stay informed about
                        crime trends and public safety across Canada.
                    </p>
                </div>

                <!-- Link Columns -->
                <div
                    v-for="(section, key) in footerLinks"
                    :key="key"
                    class="lg:col-span-1"
                >
                    <h3
                        class="mb-4 text-sm font-semibold tracking-wider text-zinc-400 uppercase"
                    >
                        {{ section.title }}
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="link in section.links" :key="link.name">
                            <a
                                v-if="link.external"
                                :href="link.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-zinc-500 transition-colors hover:text-white"
                            >
                                {{ link.name }}
                            </a>
                            <Link
                                v-else
                                :href="link.href"
                                class="text-sm text-zinc-500 transition-colors hover:text-white"
                            >
                                {{ link.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-zinc-800 pt-8 md:flex-row"
            >
                <p class="text-sm text-zinc-500">
                    &copy; {{ currentYear }} StreetCode.net. All rights
                    reserved.
                </p>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-zinc-600">Canada</span>
                </div>
            </div>
        </div>
    </footer>
</template>

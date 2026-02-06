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
            { name: 'Violent Crime', href: '/?tag=violent-crime' },
            { name: 'Property Crime', href: '/?tag=property-crime' },
            { name: 'Drug Crime', href: '/?tag=drug-crime' },
            { name: 'Organized Crime', href: '/?tag=organized-crime' },
            { name: 'Criminal Justice', href: '/?tag=criminal-justice' },
        ],
    },
    locations: {
        title: 'Countries',
        links: [
            { name: 'United States', href: '/crime/us' },
            { name: 'Canada', href: '/crime/ca' },
            { name: 'United Kingdom', href: '/crime/gb' },
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
};
</script>

<template>
    <footer class="border-t border-public-border bg-public-bg-subtle">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
                <!-- Brand Column -->
                <div class="lg:col-span-1">
                    <Link
                        href="/"
                        class="font-heading text-lg font-bold text-public-text hover:text-public-accent"
                    >
                        StreetCode
                    </Link>
                    <p class="mt-4 text-sm text-public-text-secondary">
                        Crime news aggregation. Stay informed about crime
                        trends and public safety in your community.
                    </p>
                </div>

                <!-- Link Columns -->
                <div
                    v-for="(section, key) in footerLinks"
                    :key="key"
                    class="lg:col-span-1"
                >
                    <h3
                        class="mb-4 text-sm font-semibold tracking-wider text-public-text-muted uppercase"
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
                                class="text-sm text-public-text-secondary transition-colors hover:text-public-accent"
                            >
                                {{ link.name }}
                            </a>
                            <Link
                                v-else
                                :href="link.href"
                                class="text-sm text-public-text-secondary transition-colors hover:text-public-accent"
                            >
                                {{ link.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-public-border pt-8 md:flex-row"
            >
                <p class="text-sm text-public-text-muted">
                    &copy; {{ currentYear }} StreetCode.net. All rights
                    reserved.
                </p>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-public-text-muted"
                        >streetcode.net</span
                    >
                </div>
            </div>
        </div>
    </footer>
</template>

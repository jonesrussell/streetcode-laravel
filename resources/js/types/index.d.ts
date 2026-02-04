import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    children?: NavItem[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    logo_url: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_admin?: boolean;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Article {
    id: number;
    news_source_id: number;
    author_id: number | null;
    title: string;
    excerpt: string | null;
    content: string | null;
    url: string;
    image_url: string | null;
    author: string | null;
    published_at: string | null;
    view_count: number;
    is_featured: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    metadata?: Record<string, unknown> & { og_description?: string };
    news_source?: NewsSource;
    tags?: Tag[];
    author?: User;
}

export interface NewsSource {
    id: number;
    name: string;
    slug: string;
    url: string;
    logo_url: string | null;
    description: string | null;
    credibility_score: number | null;
    bias_rating:
        | 'left'
        | 'center-left'
        | 'center'
        | 'center-right'
        | 'right'
        | null;
    factual_reporting_score: number | null;
    ownership: string | null;
    bias_color?: string;
    articles_count?: number;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    type: string;
    color: string | null;
    description: string | null;
    article_count: number;
}

export interface PaginatedArticles {
    data: Article[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    per_page: number;
    first_page_url: string;
    last_page_url: string;
    prev_page_url: string | null;
    next_page_url: string | null;
    path: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    /** Present when backend uses nested meta (e.g. Articles/Index, Dashboard). */
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface CategoryArticles {
    tag: Tag;
    articles: Article[];
}

import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import SourceCredibilityBadge from '@/components/SourceCredibilityBadge.vue';
import type { NewsSource } from '@/types';

function createSource(overrides: Partial<NewsSource> = {}): NewsSource {
    return {
        id: 1,
        name: 'Test News',
        slug: 'test-news',
        url: 'https://example.com',
        logo_url: null,
        description: null,
        credibility_score: 75,
        bias_rating: 'center',
        factual_reporting_score: null,
        ownership: null,
        ...overrides,
    };
}

describe('SourceCredibilityBadge', () => {
    it('renders source name', () => {
        const source = createSource({ name: 'Canadian Press' });
        const wrapper = mount(SourceCredibilityBadge, { props: { source } });
        expect(wrapper.text()).toContain('Canadian Press');
    });

    it('renders with high credibility score', () => {
        const source = createSource({ credibility_score: 85, name: 'High Cred' });
        const wrapper = mount(SourceCredibilityBadge, { props: { source } });
        expect(wrapper.text()).toContain('High Cred');
    });
});

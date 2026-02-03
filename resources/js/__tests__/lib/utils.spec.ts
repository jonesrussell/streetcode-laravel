import { describe, expect, it } from 'vitest';
import { cn, toUrl, urlIsActive } from '@/lib/utils';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', true && 'visible')).toBe('base visible');
    });

    it('merges tailwind classes correctly', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });
});

describe('toUrl', () => {
    it('returns string href as-is', () => {
        expect(toUrl('/articles')).toBe('/articles');
    });

    it('returns url from object href', () => {
        expect(toUrl({ url: '/dashboard', method: 'get' })).toBe('/dashboard');
    });
});

describe('urlIsActive', () => {
    it('returns true when url matches current', () => {
        expect(urlIsActive('/dashboard', '/dashboard')).toBe(true);
        expect(urlIsActive({ url: '/settings', method: 'get' }, '/settings')).toBe(true);
    });

    it('returns false when url does not match', () => {
        expect(urlIsActive('/dashboard', '/settings')).toBe(false);
    });
});

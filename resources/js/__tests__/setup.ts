import { vi } from 'vitest';

// Stub Inertia router.visit / router.get etc. when components are tested in isolation
vi.mock('@inertiajs/vue3', () => ({
    router: {
        visit: vi.fn(),
        get: vi.fn(),
        post: vi.fn(),
    },
    Link: {
        name: 'Link',
        template: '<a><slot /></a>',
    },
    Head: {
        name: 'Head',
        template: '<div></div>',
    },
    usePage: vi.fn(() => ({})),
    Form: {
        name: 'Form',
        template: '<form><slot /></form>',
    },
}));

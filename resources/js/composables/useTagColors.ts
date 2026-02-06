const dotColorMap: Record<string, string> = {
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
};

const badgeColorMap: Record<string, string> = {
    red: 'bg-red-500/20 text-red-600 border-red-500/30 dark:text-red-400',
    orange: 'bg-orange-500/20 text-orange-600 border-orange-500/30 dark:text-orange-400',
    yellow: 'bg-yellow-500/20 text-yellow-600 border-yellow-500/30 dark:text-yellow-400',
    green: 'bg-green-500/20 text-green-600 border-green-500/30 dark:text-green-400',
    blue: 'bg-blue-500/20 text-blue-600 border-blue-500/30 dark:text-blue-400',
    purple: 'bg-purple-500/20 text-purple-600 border-purple-500/30 dark:text-purple-400',
};

export function getTagDotColor(color: string | null): string {
    return dotColorMap[color || ''] || 'bg-slate-400';
}

export function getTagBadgeColor(color: string | null): string {
    return badgeColorMap[color || ''] || 'bg-slate-500/20 text-slate-600 border-slate-500/30 dark:text-slate-400';
}

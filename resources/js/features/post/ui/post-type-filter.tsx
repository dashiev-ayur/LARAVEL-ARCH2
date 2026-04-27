import { Link } from '@inertiajs/react';

import { Button } from '@/shared/ui/button';
import { buildPostTypeFilterHref } from '../lib/build-post-type-href';
import type { PostTypeUiItem } from '../model/types';

type PostTypeFilterProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    postTypes: readonly string[];
    postTypeUi: Record<string, PostTypeUiItem>;
    query?: Record<string, string | number | boolean | undefined>;
};

/**
 * Фильтр списка записей по типу (Inertia-навигация, без логики в shared).
 */
export function PostTypeFilter({
    currentTeam,
    currentOrg,
    activeType,
    postTypes,
    postTypeUi,
    query,
}: PostTypeFilterProps) {
    return (
        <div className="flex min-w-0 flex-wrap items-center gap-2">
            {postTypes.map((type) => {
                const isActive = type === activeType;
                const label = postTypeUi[type]?.filterButtonTitle ?? type;
                const href = buildPostTypeFilterHref(currentTeam, currentOrg, type, query);

                return (
                    <Button key={type} variant={isActive ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={href}>{label}</Link>
                    </Button>
                );
            })}
        </div>
    );
}

import { Search, SlidersHorizontal } from 'lucide-react';
import { Button } from '@/shared/ui/button';
import type { PostTypeUiItem } from '../model/types';
import { ButtonNewPost } from './button-new-post';
import { PostTypeFilter } from './post-type-filter';

type PostsListToolbarProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    postTypes: readonly string[];
    postTypeUi: Record<string, PostTypeUiItem>;
    query?: Record<string, string | number | boolean | undefined>;
    searchVisible: boolean;
    filtersVisible: boolean;
    hasActiveSearch: boolean;
    hasActiveColumnFilters: boolean;
    onToggleSearch: () => void;
    onToggleFilters: () => void;
};

/**
 * Тулбар списка: фильтр по типу + сценарное действие «новая запись».
 */
export function PostsListToolbar({
    currentTeam,
    currentOrg,
    activeType,
    postTypes,
    postTypeUi,
    query,
    searchVisible,
    filtersVisible,
    hasActiveSearch,
    hasActiveColumnFilters,
    onToggleSearch,
    onToggleFilters,
}: PostsListToolbarProps) {
    return (
        <>
            <PostTypeFilter
                currentTeam={currentTeam}
                currentOrg={currentOrg}
                activeType={activeType}
                postTypes={postTypes}
                postTypeUi={postTypeUi}
                query={query}
            />
            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant={searchVisible ? 'secondary' : 'ghost'}
                    className={`h-8 w-8 shrink-0 px-0 ${!searchVisible && hasActiveSearch ? 'bg-muted text-foreground' : ''}`}
                    aria-label={searchVisible ? 'Скрыть поиск' : 'Показать поиск'}
                    onClick={onToggleSearch}
                >
                    <Search className="size-4" />
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant={filtersVisible ? 'secondary' : 'ghost'}
                    className={`h-8 w-8 shrink-0 px-0 ${!filtersVisible && hasActiveColumnFilters ? 'bg-muted text-foreground' : ''}`}
                    aria-label={filtersVisible ? 'Скрыть фильтры' : 'Показать фильтры'}
                    onClick={onToggleFilters}
                >
                    <SlidersHorizontal className="size-4" />
                </Button>
                <ButtonNewPost
                    className="shrink-0"
                    newButtonTitle={postTypeUi[activeType]?.newButtonTitle ?? 'Новая запись'}
                />
            </div>
        </>
    );
}

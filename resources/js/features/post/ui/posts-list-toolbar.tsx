import type { PostTypeUiItem } from '../model/types';
import { ButtonNewPost } from './button-new-post';
import { PostTypeFilter } from './post-type-filter';

type PostsListToolbarProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    postTypes: readonly string[];
    postTypeUi: Record<string, PostTypeUiItem>;
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
}: PostsListToolbarProps) {
    return (
        <>
            <PostTypeFilter
                currentTeam={currentTeam}
                currentOrg={currentOrg}
                activeType={activeType}
                postTypes={postTypes}
                postTypeUi={postTypeUi}
            />
            <ButtonNewPost
                className="shrink-0"
                newButtonTitle={postTypeUi[activeType]?.newButtonTitle ?? 'Новая запись'}
            />
        </>
    );
}

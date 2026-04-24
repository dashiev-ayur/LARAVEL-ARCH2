import type { PostListRow } from '@/entities/post';

/**
 * Подписи кнопок фильтра/создания с бэка (`PostTypeHandler::toInertiaArray`), без дублирования enum на фронте.
 */
export type PostTypeUiItem = {
    filterButtonTitle: string;
    newButtonTitle: string;
};

/**
 * Пропы Inertia для страницы списка записей (согласованы с `PostController@index`).
 */
export type PostsListPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    /** Код активного типа из URL/контроллера. */
    activeType: string;
    postTypeUi: Record<string, PostTypeUiItem>;
    /** Порядок и набор кодов с бэка (`PostType::values()`). */
    postTypes: readonly string[];
    posts: PostListRow[];
};

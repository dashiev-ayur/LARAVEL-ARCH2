import type { PostListRow } from '@/entities/post';

/**
 * Подписи кнопок фильтра/создания с бэка (`PostTypeHandler::toInertiaArray`), без дублирования enum на фронте.
 */
export type PostTypeUiItem = {
    filterButtonTitle: string;
    newButtonTitle: string;
};

export type PostsPagination = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
};

export type PostsListFilters = {
    search: string;
    title: string;
    status: string;
    publishedAt: string;
    updatedAt: string;
};

export type PostsListSorting = {
    sortBy: 'title' | 'status' | 'published_at' | 'updated_at' | 'id';
    sortDirection: 'asc' | 'desc';
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
    postsPagination: PostsPagination;
    postsFilters: PostsListFilters;
    postsSorting: PostsListSorting;
};

/**
 * Пропы Inertia для страницы редактирования записи.
 */
export type PostEditPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    postTypeUi: Record<string, PostTypeUiItem>;
    postTypes: readonly string[];
    post: PostListRow;
};

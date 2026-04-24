/**
 * Слой features — сценарии (фильтры, смена org, 2FA и т.д.).
 * Пилот: `@/features/post` — список записей (фильтр + действие).
 */
export type { PostTypeUiItem, PostsListPageProps } from './post';
export {
    ButtonNewPost,
    buildPostTypeFilterHref,
    PostTypeFilter,
    PostsListTable,
    PostsListToolbar,
    usePostsListPage,
} from './post';

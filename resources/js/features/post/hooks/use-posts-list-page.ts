import { usePage } from '@inertiajs/react';

import type { PostsListPageProps } from '../model/types';

/**
 * Узкий хук над `usePage` для сценария «список записей»; источник правды — Inertia, без дублирования пропов в контексте.
 */
export function usePostsListPage(): { props: PostsListPageProps } {
    return usePage<PostsListPageProps>();
}

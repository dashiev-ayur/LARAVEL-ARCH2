/**
 * Статус публикации страницы сайта.
 */
export type PageStatus = 'draft' | 'review' | 'published';

/**
 * Строка списка страниц из Inertia-пропов.
 */
export interface PageListRow {
    id: number;
    parent_id: number | null;
    depth: number;
    slug: string;
    path: string;
    title: string;
    status: PageStatus;
    seo_title: string | null;
    meta_description: string | null;
    noindex: boolean;
    sort_order: number;
    children_count: number;
    updated_at: string | null;
}

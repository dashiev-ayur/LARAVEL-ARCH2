/**
 * Строка списка категорий из Inertia-пропов.
 */
export interface CategoryListRow {
    id: number;
    parent_id: number | null;
    depth: number;
    type: string;
    slug: string;
    title: string;
    sort_order: number;
    posts_count: number;
    children_count: number;
    updated_at: string | null;
}

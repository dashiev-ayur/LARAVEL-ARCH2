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
    updated_at: string | null;
}

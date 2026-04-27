/**
 * Строка списка записей (проп Inertia с бэка; отдельного DTO в @/types пока нет).
 */
export interface PostListRow {
    id: number;
    type: string;
    status: string;
    slug: string;
    title: string;
    excerpt: string | null;
    published_at: string | null;
    updated_at: string | null;
}

import { byType } from '@/routes/posts';

/**
 * URL фильтра по типу записи (Wayfinder, без сырых строк).
 * При отсутствии team/org — прежний fallback для обрезанного состояния маршрута.
 */
export function buildPostTypeFilterHref(
    currentTeam: { slug: string } | null,
    currentOrg: { slug: string } | null,
    type: string,
    query?: Record<string, string | number | boolean | undefined>,
): string {
    if (!currentTeam || !currentOrg) {
        return '/posts';
    }

    return byType.url({
        current_team: currentTeam.slug,
        current_org: currentOrg.slug,
        type,
    }, query ? { query } : undefined);
}

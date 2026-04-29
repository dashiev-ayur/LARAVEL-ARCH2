import type { TreeDndItem, TreeDndPayloadItem } from './types';

export function buildTreeReorderPayload<TItem extends TreeDndItem>(
    items: TItem[],
): TreeDndPayloadItem[] {
    const sortOrderByParentId = new Map<number | null, number>();

    return items.map((item) => {
        const sortOrder = sortOrderByParentId.get(item.parent_id) ?? 0;

        sortOrderByParentId.set(item.parent_id, sortOrder + 1);

        return {
            id: item.id,
            parent_id: item.parent_id,
            sort_order: sortOrder,
        };
    });
}

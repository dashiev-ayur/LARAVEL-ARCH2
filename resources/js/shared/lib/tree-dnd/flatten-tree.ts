import type { TreeDndItem } from './types';

export function buildChildrenByParentId<TItem extends TreeDndItem>(
    items: TItem[],
): Map<number | null, TItem[]> {
    const childrenByParentId = new Map<number | null, TItem[]>();

    for (const item of items) {
        childrenByParentId.set(item.parent_id, [
            ...(childrenByParentId.get(item.parent_id) ?? []),
            item,
        ]);
    }

    return childrenByParentId;
}

export function isTreeItemDescendantOf<TItem extends TreeDndItem>(
    items: TItem[],
    descendantId: number,
    ancestorId: number,
): boolean {
    const parentById = new Map(
        items.map((item) => [item.id, item.parent_id] as const),
    );
    let parentId = parentById.get(descendantId) ?? null;

    while (parentId !== null) {
        if (parentId === ancestorId) {
            return true;
        }

        parentId = parentById.get(parentId) ?? null;
    }

    return false;
}

import type { TreeDndItem, TreeMoveProjection } from './types';

type ProjectTreeMoveOptions<TItem extends TreeDndItem> = {
    items: TItem[];
    activeId: number;
    overId: number | null;
    horizontalOffset: number;
    indentationWidth: number;
};

function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}

function getSubtreeEndIndex<TItem extends TreeDndItem>(
    items: TItem[],
    activeIndex: number,
): number {
    const activeDepth = items[activeIndex]?.depth ?? 0;
    let endIndex = activeIndex + 1;

    while (endIndex < items.length && items[endIndex].depth > activeDepth) {
        endIndex++;
    }

    return endIndex;
}

function findParentIdForDepth<TItem extends TreeDndItem>(
    items: TItem[],
    startIndex: number,
    depth: number,
): number | null {
    if (depth === 0) {
        return null;
    }

    for (let index = startIndex; index >= 0; index--) {
        if (items[index].depth === depth - 1) {
            return items[index].id;
        }
    }

    return null;
}

export function projectTreeMove<TItem extends TreeDndItem>({
    items,
    activeId,
    overId,
    horizontalOffset,
    indentationWidth,
}: ProjectTreeMoveOptions<TItem>): TreeMoveProjection<TItem> | null {
    if (overId === null || activeId === overId) {
        return null;
    }

    const activeIndex = items.findIndex((item) => item.id === activeId);
    const overIndex = items.findIndex((item) => item.id === overId);

    if (activeIndex === -1 || overIndex === -1) {
        return null;
    }

    const activeItem = items[activeIndex];
    const subtreeEndIndex = getSubtreeEndIndex(items, activeIndex);
    const subtreeItems = items.slice(activeIndex, subtreeEndIndex);
    const remainingItems = [
        ...items.slice(0, activeIndex),
        ...items.slice(subtreeEndIndex),
    ];
    const overIndexInRemaining = remainingItems.findIndex(
        (item) => item.id === overId,
    );

    if (overIndexInRemaining === -1) {
        return null;
    }

    const insertIndex =
        activeIndex < overIndex
            ? overIndexInRemaining + 1
            : overIndexInRemaining;
    const previousItem = remainingItems[insertIndex - 1] ?? null;
    const nextItem = remainingItems[insertIndex] ?? null;
    const projectedDepthOffset = Math.round(
        horizontalOffset / indentationWidth,
    );
    const maxDepth = previousItem ? previousItem.depth + 1 : 0;
    const minDepth = nextItem ? nextItem.depth : 0;
    const depth = clamp(
        activeItem.depth + projectedDepthOffset,
        minDepth,
        maxDepth,
    );
    const depthDelta = depth - activeItem.depth;
    const parentId = findParentIdForDepth(remainingItems, insertIndex - 1, depth);
    const movedSubtree = subtreeItems.map((item, index) => ({
        ...item,
        parent_id: index === 0 ? parentId : item.parent_id,
        depth: item.depth + depthDelta,
    }));
    const projectedItems = [
        ...remainingItems.slice(0, insertIndex),
        ...movedSubtree,
        ...remainingItems.slice(insertIndex),
    ];

    return {
        items: projectedItems,
        activeItem: {
            ...activeItem,
            parent_id: parentId,
            depth,
        },
        overId,
        depth,
        parent_id: parentId,
    };
}

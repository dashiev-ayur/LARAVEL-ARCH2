export type TreeDndItem = {
    id: number;
    parent_id: number | null;
    depth: number;
};

export type TreeDndPayloadItem = {
    id: number;
    parent_id: number | null;
    sort_order: number;
};

export type TreeMoveProjection<TItem extends TreeDndItem> = {
    items: TItem[];
    activeItem: TItem;
    overId: number;
    depth: number;
    parent_id: number | null;
};

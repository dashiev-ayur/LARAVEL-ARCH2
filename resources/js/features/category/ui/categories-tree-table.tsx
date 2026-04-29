import {
    closestCenter,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    useDraggable,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type {
    DragEndEvent,
    DragMoveEvent,
    DragOverEvent,
    DragStartEvent,
} from '@dnd-kit/core';
import { router } from '@inertiajs/react';
import { GripVertical, Pencil } from 'lucide-react';
import {
    Fragment,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { CSSProperties } from 'react';
import type { CategoryListRow } from '@/entities/category';
import { reorder as reorderCategories } from '@/routes/categories';
import { formatDateTime } from '@/shared/lib/format-date-time';
import {
    buildTreeReorderPayload,
    projectTreeMove,
} from '@/shared/lib/tree-dnd';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Table, tableColumnVariant } from '@/shared/ui/table';
import { CreateCategoryDialog } from './create-category-dialog';

type CategoriesTreeTableProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    categories: CategoryListRow[];
};

type CategoryTreeRowProps = {
    category: CategoryListRow;
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    categories: CategoryListRow[];
    dragging: boolean;
    dropTarget: boolean;
    disabled: boolean;
};

type GhostPlacement = {
    beforeId: number | null;
    category: CategoryListRow;
    depth: number;
    signature: string;
};

const INDENTATION_WIDTH = 24;

type ProjectionInput = {
    activeId: number;
    overId: number | null;
    horizontalOffset: number;
};

function toCategoryId(id: string | number): number | null {
    const categoryId = Number(id);

    return Number.isFinite(categoryId) ? categoryId : null;
}

function CategoryDragOverlay({ category }: { category: CategoryListRow }) {
    return (
        <div className="rounded-md border bg-background/70 px-4 py-3 text-sm opacity-80 shadow-lg backdrop-blur-sm">
            <div className="flex items-center gap-2">
                <GripVertical className="h-4 w-4 text-muted-foreground" />
                <span className="font-medium">{category.title}</span>
            </div>
        </div>
    );
}

function CategoryGhostRow({
    category,
    depth,
}: {
    category: CategoryListRow;
    depth: number;
}) {
    const depthStyle: CSSProperties = {
        paddingInlineStart: `${depth * 1.5}rem`,
    };

    return (
        <Table.Row className="pointer-events-none border-y-2 border-dashed border-primary/50 bg-primary/5">
            <Table.Cell variant="select" />
            <Table.Cell variant="drag">
                <GripVertical className="mx-auto h-4 w-4 text-primary/70" />
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('title')}>
                <div
                    className="flex items-center gap-2 text-primary/80"
                    style={depthStyle}
                >
                    {depth > 0 ? <span>--</span> : null}
                    <span className="font-medium">{category.title}</span>
                </div>
            </Table.Cell>
            <Table.Cell
                variant={tableColumnVariant('slug')}
                className="font-mono text-xs text-primary/60"
            >
                {category.slug}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('type')}>
                {category.type}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('updated_at')}>
                {formatDateTime(category.updated_at)}
            </Table.Cell>
            <Table.Cell variant="actions" />
        </Table.Row>
    );
}

function buildGhostPlacement(
    projectionItems: CategoryListRow[],
    activeId: number,
): GhostPlacement | null {
    const activeIndex = projectionItems.findIndex(
        (category) => category.id === activeId,
    );

    if (activeIndex === -1) {
        return null;
    }

    const activeCategory = projectionItems[activeIndex];
    let nextSiblingOrAncestor: CategoryListRow | null = null;

    for (let index = activeIndex + 1; index < projectionItems.length; index++) {
        if (projectionItems[index].depth <= activeCategory.depth) {
            nextSiblingOrAncestor = projectionItems[index];
            break;
        }
    }

    return {
        beforeId: nextSiblingOrAncestor?.id ?? null,
        category: activeCategory,
        depth: activeCategory.depth,
        signature: `${activeCategory.id}:${activeCategory.parent_id ?? 'root'}:${activeCategory.depth}:${nextSiblingOrAncestor?.id ?? 'end'}`,
    };
}

function CategoryTreeRow({
    category,
    currentTeam,
    currentOrg,
    activeType,
    categories,
    dragging,
    dropTarget,
    disabled,
}: CategoryTreeRowProps) {
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: category.id,
        disabled,
    });
    const {
        attributes,
        listeners,
        setActivatorNodeRef,
        setNodeRef: setDraggableNodeRef,
        isDragging,
    } = useDraggable({
        id: category.id,
        disabled,
    });
    const setRowNodeRef = useCallback(
        (node: HTMLTableRowElement | null) => {
            setDroppableNodeRef(node);
            setDraggableNodeRef(node);
        },
        [setDroppableNodeRef, setDraggableNodeRef],
    );
    const rowClassName = [
        dropTarget ? 'border-t-2 border-t-primary' : '',
        isDragging || dragging ? 'opacity-40' : '',
    ]
        .filter(Boolean)
        .join(' ');
    const depthStyle: CSSProperties = {
        paddingInlineStart: `${category.depth * 1.5}rem`,
    };

    return (
        <Table.Row ref={setRowNodeRef} className={rowClassName}>
            <Table.Cell variant="select">
                <Checkbox aria-label={`Выбрать категорию ${category.title}`} />
            </Table.Cell>
            <Table.Cell variant="drag">
                <Button
                    ref={setActivatorNodeRef}
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-8 cursor-grab text-muted-foreground active:cursor-grabbing"
                    aria-label={`Перетащить категорию ${category.title}`}
                    disabled={disabled}
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-4 w-4" />
                </Button>
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('title')}>
                <div className="flex items-center gap-2" style={depthStyle}>
                    {category.depth > 0 ? (
                        <span className="text-muted-foreground">--</span>
                    ) : null}
                    <span className="font-medium">{category.title}</span>
                </div>
            </Table.Cell>
            <Table.Cell
                variant={tableColumnVariant('slug')}
                className="font-mono text-xs text-muted-foreground"
            >
                {category.slug}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('type')}>
                {category.type}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('updated_at')}>
                {formatDateTime(category.updated_at)}
            </Table.Cell>
            <Table.Cell variant="actions">
                <CreateCategoryDialog
                    currentTeam={currentTeam}
                    currentOrg={currentOrg}
                    activeType={activeType}
                    categories={categories}
                    category={category}
                    trigger={
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label={`Редактировать категорию ${category.title}`}
                        >
                            <Pencil className="h-4 w-4" />
                        </Button>
                    }
                />
            </Table.Cell>
        </Table.Row>
    );
}

export function CategoriesTreeTable({
    currentTeam,
    currentOrg,
    activeType,
    categories,
}: CategoriesTreeTableProps) {
    const [items, setItems] = useState(categories);
    const [activeId, setActiveId] = useState<number | null>(null);
    const [overId, setOverId] = useState<number | null>(null);
    const [ghostPlacement, setGhostPlacement] = useState<GhostPlacement | null>(
        null,
    );
    const [saving, setSaving] = useState(false);
    const snapshotRef = useRef(categories);
    const projectionFrameRef = useRef<number | null>(null);
    const pendingProjectionRef = useRef<ProjectionInput | null>(null);
    const ghostPlacementRef = useRef<GhostPlacement | null>(null);
    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor),
    );
    const activeCategory = useMemo(
        () => items.find((category) => category.id === activeId) ?? null,
        [activeId, items],
    );
    const disabled = !currentTeam || !currentOrg || saving;

    useEffect(() => {
        setItems(categories);
    }, [categories]);

    useEffect(
        () => () => {
            if (projectionFrameRef.current !== null) {
                window.cancelAnimationFrame(projectionFrameRef.current);
            }
        },
        [],
    );

    const resetDragState = useCallback(() => {
        if (projectionFrameRef.current !== null) {
            window.cancelAnimationFrame(projectionFrameRef.current);
            projectionFrameRef.current = null;
        }

        pendingProjectionRef.current = null;
        ghostPlacementRef.current = null;
        setOverId(null);
        setActiveId(null);
        setGhostPlacement(null);
    }, []);

    const applyGhostProjection = useCallback((input: ProjectionInput) => {
        const projection = projectTreeMove({
            items: snapshotRef.current,
            activeId: input.activeId,
            overId: input.overId,
            horizontalOffset: input.horizontalOffset,
            indentationWidth: INDENTATION_WIDTH,
        });
        const nextGhostPlacement = projection
            ? buildGhostPlacement(projection.items, input.activeId)
            : null;

        if (
            ghostPlacementRef.current?.signature ===
            nextGhostPlacement?.signature
        ) {
            return;
        }

        ghostPlacementRef.current = nextGhostPlacement;
        setGhostPlacement(nextGhostPlacement);
    }, []);

    const scheduleGhostProjection = useCallback(
        (input: ProjectionInput) => {
            pendingProjectionRef.current = input;

            if (projectionFrameRef.current !== null) {
                return;
            }

            projectionFrameRef.current = window.requestAnimationFrame(() => {
                projectionFrameRef.current = null;

                if (!pendingProjectionRef.current) {
                    return;
                }

                applyGhostProjection(pendingProjectionRef.current);
                pendingProjectionRef.current = null;
            });
        },
        [applyGhostProjection],
    );

    const handleDragOver = useCallback((event: DragOverEvent) => {
        const nextOverId = event.over ? toCategoryId(event.over.id) : null;

        setOverId((currentOverId) =>
            currentOverId === nextOverId ? currentOverId : nextOverId,
        );
    }, []);

    const handleDragMove = useCallback(
        (event: DragMoveEvent) => {
            const nextActiveId = toCategoryId(event.active.id);

            if (nextActiveId === null) {
                return;
            }

            scheduleGhostProjection({
                activeId: nextActiveId,
                overId: event.over ? toCategoryId(event.over.id) : null,
                horizontalOffset: event.delta.x,
            });
        },
        [scheduleGhostProjection],
    );

    const handleDragStart = (event: DragStartEvent) => {
        const nextActiveId = toCategoryId(event.active.id);

        if (nextActiveId === null) {
            return;
        }

        snapshotRef.current = items;
        setActiveId(nextActiveId);
    };

    const handleDragCancel = () => {
        resetDragState();
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const nextActiveId = toCategoryId(event.active.id);
        const finalProjection =
            nextActiveId === null
                ? null
                : projectTreeMove({
                      items: snapshotRef.current,
                      activeId: nextActiveId,
                      overId: event.over ? toCategoryId(event.over.id) : null,
                      horizontalOffset: event.delta.x,
                      indentationWidth: INDENTATION_WIDTH,
                  });
        const nextItems = finalProjection?.items ?? null;

        resetDragState();

        if (
            !currentTeam ||
            !currentOrg ||
            !nextItems ||
            event.over === null ||
            nextActiveId === null
        ) {
            return;
        }

        setItems(nextItems);
        router.patch(
            reorderCategories.url({
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
            }),
            {
                type: activeType,
                items: buildTreeReorderPayload(nextItems),
            },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onError: () => setItems(snapshotRef.current),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragStart={handleDragStart}
            onDragMove={handleDragMove}
            onDragOver={handleDragOver}
            onDragCancel={handleDragCancel}
            onDragEnd={handleDragEnd}
        >
            <Table.ScrollArea>
                <Table>
                    <Table.Header>
                        <Table.Row header>
                            <Table.Head variant="select">
                                <span className="sr-only">Выбор</span>
                            </Table.Head>
                            <Table.Head variant="drag">
                                <span className="sr-only">Перетаскивание</span>
                            </Table.Head>
                            <Table.Head variant={tableColumnVariant('title')}>
                                Название
                            </Table.Head>
                            <Table.Head variant={tableColumnVariant('slug')}>
                                Slug
                            </Table.Head>
                            <Table.Head variant={tableColumnVariant('type')}>
                                Тип
                            </Table.Head>
                            <Table.Head
                                variant={tableColumnVariant('updated_at')}
                            >
                                Обновлено
                            </Table.Head>
                            <Table.Head variant="actions">
                                <span className="sr-only">Действия</span>
                            </Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {items.length > 0 ? (
                            <>
                                {items.map((category) => (
                                    <Fragment key={category.id}>
                                        {ghostPlacement?.beforeId ===
                                        category.id ? (
                                            <CategoryGhostRow
                                                key={`ghost-${ghostPlacement.signature}`}
                                                category={
                                                    ghostPlacement.category
                                                }
                                                depth={ghostPlacement.depth}
                                            />
                                        ) : null}
                                        <CategoryTreeRow
                                            key={category.id}
                                            category={category}
                                            currentTeam={currentTeam}
                                            currentOrg={currentOrg}
                                            activeType={activeType}
                                            categories={items}
                                            dragging={category.id === activeId}
                                            dropTarget={category.id === overId}
                                            disabled={disabled}
                                        />
                                    </Fragment>
                                ))}
                                {ghostPlacement?.beforeId === null ? (
                                    <CategoryGhostRow
                                        key={`ghost-${ghostPlacement.signature}`}
                                        category={ghostPlacement.category}
                                        depth={ghostPlacement.depth}
                                    />
                                ) : null}
                            </>
                        ) : (
                            <Table.Row>
                                <Table.Cell
                                    colSpan={7}
                                    className="py-8 text-center text-sm text-muted-foreground"
                                >
                                    Категорий пока нет.
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table>
            </Table.ScrollArea>
            <DragOverlay>
                {activeCategory ? (
                    <CategoryDragOverlay category={activeCategory} />
                ) : null}
            </DragOverlay>
        </DndContext>
    );
}

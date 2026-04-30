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
import type { PageListRow, PageStatus } from '@/entities/page';
import { reorder as reorderPages } from '@/routes/pages';
import { formatDateTime } from '@/shared/lib/format-date-time';
import {
    buildTreeReorderPayload,
    projectTreeMove,
} from '@/shared/lib/tree-dnd';
import { Badge } from '@/shared/ui/badge';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Table, tableColumnVariant } from '@/shared/ui/table';
import { PageDialog } from './page-dialog';

type StructureTreeTableProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    pages: PageListRow[];
    pageStatuses: readonly PageStatus[];
};

type PageTreeRowProps = {
    page: PageListRow;
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    pages: PageListRow[];
    pageStatuses: readonly PageStatus[];
    dragging: boolean;
    dropTarget: boolean;
    disabled: boolean;
};

type GhostPlacement = {
    beforeId: number | null;
    page: PageListRow;
    depth: number;
    signature: string;
};

type ProjectionInput = {
    activeId: number;
    overId: number | null;
    horizontalOffset: number;
};

const INDENTATION_WIDTH = 24;

const statusLabels: Record<PageStatus, string> = {
    draft: 'Черновик',
    review: 'На проверке',
    published: 'Опубликована',
};

function toPageId(id: string | number): number | null {
    const pageId = Number(id);

    return Number.isFinite(pageId) ? pageId : null;
}

function PageDragOverlay({ page }: { page: PageListRow }) {
    return (
        <div className="rounded-md border bg-background/70 px-4 py-3 text-sm opacity-80 shadow-lg backdrop-blur-sm">
            <div className="flex items-center gap-2">
                <GripVertical className="h-4 w-4 text-muted-foreground" />
                <span className="font-medium">{page.title}</span>
            </div>
        </div>
    );
}

function PageGhostRow({ page, depth }: { page: PageListRow; depth: number }) {
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
                    <span className="font-medium">{page.title}</span>
                </div>
            </Table.Cell>
            <Table.Cell
                variant={tableColumnVariant('path')}
                className="font-mono text-xs text-primary/60"
            >
                {page.path}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('status')}>
                {statusLabels[page.status] ?? page.status}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('updated_at')}>
                {formatDateTime(page.updated_at)}
            </Table.Cell>
            <Table.Cell variant="actions" />
        </Table.Row>
    );
}

function buildGhostPlacement(
    projectionItems: PageListRow[],
    activeId: number,
): GhostPlacement | null {
    const activeIndex = projectionItems.findIndex((page) => page.id === activeId);

    if (activeIndex === -1) {
        return null;
    }

    const activePage = projectionItems[activeIndex];
    let nextSiblingOrAncestor: PageListRow | null = null;

    for (let index = activeIndex + 1; index < projectionItems.length; index++) {
        if (projectionItems[index].depth <= activePage.depth) {
            nextSiblingOrAncestor = projectionItems[index];
            break;
        }
    }

    return {
        beforeId: nextSiblingOrAncestor?.id ?? null,
        page: activePage,
        depth: activePage.depth,
        signature: `${activePage.id}:${activePage.parent_id ?? 'root'}:${activePage.depth}:${nextSiblingOrAncestor?.id ?? 'end'}`,
    };
}

function PageTreeRow({
    page,
    currentTeam,
    currentOrg,
    pages,
    pageStatuses,
    dragging,
    dropTarget,
    disabled,
}: PageTreeRowProps) {
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: page.id,
        disabled,
    });
    const {
        attributes,
        listeners,
        setActivatorNodeRef,
        setNodeRef: setDraggableNodeRef,
        isDragging,
    } = useDraggable({
        id: page.id,
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
        paddingInlineStart: `${page.depth * 1.5}rem`,
    };

    return (
        <Table.Row ref={setRowNodeRef} className={rowClassName}>
            <Table.Cell variant="select">
                <Checkbox aria-label={`Выбрать страницу ${page.title}`} />
            </Table.Cell>
            <Table.Cell variant="drag">
                <Button
                    ref={setActivatorNodeRef}
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-8 cursor-grab text-muted-foreground active:cursor-grabbing"
                    aria-label={`Перетащить страницу ${page.title}`}
                    disabled={disabled}
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-4 w-4" />
                </Button>
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('title')}>
                <div className="flex items-center gap-2" style={depthStyle}>
                    {page.depth > 0 ? (
                        <span className="text-muted-foreground">--</span>
                    ) : null}
                    <span className="font-medium">{page.title}</span>
                </div>
            </Table.Cell>
            <Table.Cell
                variant={tableColumnVariant('path')}
                className="font-mono text-xs text-muted-foreground"
            >
                {page.path}
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('status')}>
                <Badge variant={page.status === 'published' ? 'default' : 'secondary'}>
                    {statusLabels[page.status] ?? page.status}
                </Badge>
            </Table.Cell>
            <Table.Cell variant={tableColumnVariant('updated_at')}>
                {formatDateTime(page.updated_at)}
            </Table.Cell>
            <Table.Cell variant="actions">
                <PageDialog
                    currentTeam={currentTeam}
                    currentOrg={currentOrg}
                    pages={pages}
                    pageStatuses={pageStatuses}
                    page={page}
                    trigger={
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label={`Редактировать страницу ${page.title}`}
                        >
                            <Pencil className="h-4 w-4" />
                        </Button>
                    }
                />
            </Table.Cell>
        </Table.Row>
    );
}

export function StructureTreeTable({
    currentTeam,
    currentOrg,
    pages,
    pageStatuses,
}: StructureTreeTableProps) {
    const [items, setItems] = useState(pages);
    const [activeId, setActiveId] = useState<number | null>(null);
    const [overId, setOverId] = useState<number | null>(null);
    const [ghostPlacement, setGhostPlacement] = useState<GhostPlacement | null>(
        null,
    );
    const [saving, setSaving] = useState(false);
    const snapshotRef = useRef(pages);
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
    const activePage = useMemo(
        () => items.find((page) => page.id === activeId) ?? null,
        [activeId, items],
    );
    const disabled = !currentTeam || !currentOrg || saving;

    useEffect(() => {
        setItems(pages);
    }, [pages]);

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
        const nextOverId = event.over ? toPageId(event.over.id) : null;

        setOverId((currentOverId) =>
            currentOverId === nextOverId ? currentOverId : nextOverId,
        );
    }, []);

    const handleDragMove = useCallback(
        (event: DragMoveEvent) => {
            const nextActiveId = toPageId(event.active.id);

            if (nextActiveId === null) {
                return;
            }

            scheduleGhostProjection({
                activeId: nextActiveId,
                overId: event.over ? toPageId(event.over.id) : null,
                horizontalOffset: event.delta.x,
            });
        },
        [scheduleGhostProjection],
    );

    const handleDragStart = (event: DragStartEvent) => {
        const nextActiveId = toPageId(event.active.id);

        if (nextActiveId === null) {
            return;
        }

        snapshotRef.current = items;
        setActiveId(nextActiveId);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const nextActiveId = toPageId(event.active.id);
        const finalProjection =
            nextActiveId === null
                ? null
                : projectTreeMove({
                      items: snapshotRef.current,
                      activeId: nextActiveId,
                      overId: event.over ? toPageId(event.over.id) : null,
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
            reorderPages.url({
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
            }),
            {
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
            onDragCancel={resetDragState}
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
                            <Table.Head variant={tableColumnVariant('path')}>
                                Путь
                            </Table.Head>
                            <Table.Head variant={tableColumnVariant('status')}>
                                Статус
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
                                {items.map((page) => (
                                    <Fragment key={page.id}>
                                        {ghostPlacement?.beforeId === page.id ? (
                                            <PageGhostRow
                                                key={`ghost-${ghostPlacement.signature}`}
                                                page={ghostPlacement.page}
                                                depth={ghostPlacement.depth}
                                            />
                                        ) : null}
                                        <PageTreeRow
                                            key={page.id}
                                            page={page}
                                            currentTeam={currentTeam}
                                            currentOrg={currentOrg}
                                            pages={items}
                                            pageStatuses={pageStatuses}
                                            dragging={page.id === activeId}
                                            dropTarget={page.id === overId}
                                            disabled={disabled}
                                        />
                                    </Fragment>
                                ))}
                                {ghostPlacement?.beforeId === null ? (
                                    <PageGhostRow
                                        key={`ghost-${ghostPlacement.signature}`}
                                        page={ghostPlacement.page}
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
                                    Структура сайта пока пуста.
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table>
            </Table.ScrollArea>
            <DragOverlay>
                {activePage ? <PageDragOverlay page={activePage} /> : null}
            </DragOverlay>
        </DndContext>
    );
}

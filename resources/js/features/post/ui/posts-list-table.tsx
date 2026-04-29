import { Link, router } from '@inertiajs/react';
import {
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    getPaginationRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type {
    PaginationState,
    SortingState,
    Updater,
} from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import type { PostListRow } from '@/entities/post';
import { PostStatusCell, PostTitleExcerptCell } from '@/entities/post';
import { byType, edit as editPost, index as postsIndex } from '@/routes/posts';
import { formatDateTime } from '@/shared/lib/format-date-time';
import { useDebounce } from '@/shared/lib/hooks/use-debounce';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Input } from '@/shared/ui/input';
import {
    Table,
    TablePagination,
    TableSortableColumnHeader,
    tableColumnVariant,
} from '@/shared/ui/table';
import type { PostsListPageProps } from '../model/types';
import { PostsListToolbar } from './posts-list-toolbar';

const columnHelper = createColumnHelper<PostListRow>();

type Props = Pick<
    PostsListPageProps,
    | 'posts'
    | 'activeType'
    | 'currentTeam'
    | 'currentOrg'
    | 'postTypes'
    | 'postTypeUi'
    | 'postsPagination'
    | 'postsFilters'
    | 'postsSorting'
>;

export function PostsListTable({
    posts,
    activeType,
    currentTeam,
    currentOrg,
    postTypes,
    postTypeUi,
    postsPagination,
    postsFilters,
    postsSorting,
}: Props) {
    const columns = useMemo(
        () => [
            columnHelper.display({
                id: 'select',
                header: () => <span className="sr-only">Выбор</span>,
                cell: ({ row }) => (
                    <Checkbox
                        aria-label={`Выбрать запись ${row.original.title}`}
                    />
                ),
                enableSorting: false,
            }),
            columnHelper.accessor('title', {
                header: ({ column }) => (
                    <TableSortableColumnHeader column={column}>
                        Заголовок
                    </TableSortableColumnHeader>
                ),
                cell: ({ row }) => (
                    <PostTitleExcerptCell
                        title={row.original.title}
                        excerpt={row.original.excerpt}
                    />
                ),
            }),
            columnHelper.accessor('status', {
                header: ({ column }) => (
                    <TableSortableColumnHeader column={column}>
                        Статус
                    </TableSortableColumnHeader>
                ),
                cell: ({ getValue }) => <PostStatusCell status={getValue()} />,
            }),
            columnHelper.accessor('published_at', {
                header: ({ column }) => (
                    <TableSortableColumnHeader column={column}>
                        Публикация
                    </TableSortableColumnHeader>
                ),
                cell: ({ getValue }) => formatDateTime(getValue()),
            }),
            columnHelper.accessor('updated_at', {
                header: ({ column }) => (
                    <TableSortableColumnHeader column={column}>
                        Обновлено
                    </TableSortableColumnHeader>
                ),
                cell: ({ getValue }) => formatDateTime(getValue()),
            }),
            columnHelper.display({
                id: 'actions',
                header: () => <span className="sr-only">Действия</span>,
                cell: ({ row }) =>
                    currentTeam && currentOrg ? (
                        <Button variant="ghost" size="icon" asChild>
                            <Link
                                href={editPost.url(
                                    {
                                        current_team: currentTeam.slug,
                                        current_org: currentOrg.slug,
                                        post: row.original.id,
                                    },
                                    {
                                        query: {
                                            page: postsPagination.currentPage,
                                            per_page: postsPagination.perPage,
                                            search:
                                                postsFilters.search ||
                                                undefined,
                                            filter_title:
                                                postsFilters.title || undefined,
                                            filter_status:
                                                postsFilters.status ||
                                                undefined,
                                            filter_published_at:
                                                postsFilters.publishedAt ||
                                                undefined,
                                            filter_updated_at:
                                                postsFilters.updatedAt ||
                                                undefined,
                                            sort_by: postsSorting.sortBy,
                                            sort_direction:
                                                postsSorting.sortDirection,
                                        },
                                    },
                                )}
                                aria-label={`Редактировать запись ${row.original.title}`}
                            >
                                <Pencil className="h-4 w-4" />
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label={`Редактировать запись ${row.original.title}`}
                            disabled
                        >
                            <Pencil className="h-4 w-4" />
                        </Button>
                    ),
                enableSorting: false,
            }),
        ],
        [currentOrg, currentTeam, postsFilters, postsPagination, postsSorting],
    );

    const pagination = useMemo<PaginationState>(
        () => ({
            pageIndex: Math.max(postsPagination.currentPage - 1, 0),
            pageSize: postsPagination.perPage,
        }),
        [postsPagination.currentPage, postsPagination.perPage],
    );

    const sorting = useMemo<SortingState>(
        () => [
            {
                id: postsSorting.sortBy,
                desc: postsSorting.sortDirection === 'desc',
            },
        ],
        [postsSorting.sortBy, postsSorting.sortDirection],
    );

    const [filterTitle, setFilterTitle] = useState(postsFilters.title);
    const [filterStatus, setFilterStatus] = useState(postsFilters.status);
    const [filterSearch, setFilterSearch] = useState(postsFilters.search);
    const [filterPublishedAt, setFilterPublishedAt] = useState(
        postsFilters.publishedAt,
    );
    const [filterUpdatedAt, setFilterUpdatedAt] = useState(
        postsFilters.updatedAt,
    );
    const hasActiveSearch = useMemo(
        () => postsFilters.search.trim() !== '',
        [postsFilters.search],
    );
    const hasActiveColumnFilters = useMemo(
        () =>
            postsFilters.title.trim() !== '' ||
            postsFilters.status.trim() !== '' ||
            postsFilters.publishedAt.trim() !== '' ||
            postsFilters.updatedAt.trim() !== '',
        [
            postsFilters.publishedAt,
            postsFilters.status,
            postsFilters.title,
            postsFilters.updatedAt,
        ],
    );
    const [visibleBlock, setVisibleBlock] = useState<
        'search' | 'filters' | null
    >(hasActiveColumnFilters ? 'filters' : hasActiveSearch ? 'search' : null);
    const debouncedSearch = useDebounce(filterSearch.trim(), 400);

    const currentQuery = useMemo(
        () => ({
            page: postsPagination.currentPage,
            per_page: postsPagination.perPage,
            search: postsFilters.search || undefined,
            filter_title: postsFilters.title || undefined,
            filter_status: postsFilters.status || undefined,
            filter_published_at: postsFilters.publishedAt || undefined,
            filter_updated_at: postsFilters.updatedAt || undefined,
            sort_by: postsSorting.sortBy,
            sort_direction: postsSorting.sortDirection,
        }),
        [
            postsFilters,
            postsPagination.currentPage,
            postsPagination.perPage,
            postsSorting,
        ],
    );

    const buildPostsListHref = useCallback(
        ({
            page,
            perPage,
            search,
            title,
            status,
            publishedAt,
            updatedAt,
            sortBy,
            sortDirection,
        }: {
            page: number;
            perPage: number;
            search: string;
            title: string;
            status: string;
            publishedAt: string;
            updatedAt: string;
            sortBy: string;
            sortDirection: string;
        }): string | null => {
            if (!currentTeam || !currentOrg) {
                return null;
            }

            const query = {
                page,
                per_page: perPage,
                search: search || undefined,
                filter_title: title || undefined,
                filter_status: status || undefined,
                filter_published_at: publishedAt || undefined,
                filter_updated_at: updatedAt || undefined,
                sort_by: sortBy,
                sort_direction: sortDirection,
            };

            if (activeType === 'page') {
                return postsIndex.url(
                    {
                        current_team: currentTeam.slug,
                        current_org: currentOrg.slug,
                    },
                    { query },
                );
            }

            return byType.url(
                {
                    current_team: currentTeam.slug,
                    current_org: currentOrg.slug,
                    type: activeType,
                },
                { query },
            );
        },
        [activeType, currentOrg, currentTeam],
    );

    const handlePaginationChange = (updater: Updater<PaginationState>) => {
        const nextPagination =
            typeof updater === 'function' ? updater(pagination) : updater;
        const nextPage = nextPagination.pageIndex + 1;

        if (
            !currentTeam ||
            !currentOrg ||
            nextPage === postsPagination.currentPage
        ) {
            return;
        }

        const href = buildPostsListHref({
            page: nextPage,
            perPage: postsPagination.perPage,
            search: postsFilters.search,
            title: postsFilters.title,
            status: postsFilters.status,
            publishedAt: postsFilters.publishedAt,
            updatedAt: postsFilters.updatedAt,
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    const handlePerPageChange = (perPage: number) => {
        const href = buildPostsListHref({
            page: 1,
            perPage,
            search: postsFilters.search,
            title: postsFilters.title,
            status: postsFilters.status,
            publishedAt: postsFilters.publishedAt,
            updatedAt: postsFilters.updatedAt,
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    const handleFiltersSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const href = buildPostsListHref({
            page: 1,
            perPage: postsPagination.perPage,
            search: postsFilters.search,
            title: filterTitle.trim(),
            status: filterStatus.trim(),
            publishedAt: filterPublishedAt,
            updatedAt: filterUpdatedAt,
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    const handleFiltersReset = () => {
        setFilterTitle('');
        setFilterStatus('');
        setFilterPublishedAt('');
        setFilterUpdatedAt('');

        const href = buildPostsListHref({
            page: 1,
            perPage: postsPagination.perPage,
            search: postsFilters.search,
            title: '',
            status: '',
            publishedAt: '',
            updatedAt: '',
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    const handleSearchReset = () => {
        setFilterSearch('');

        const href = buildPostsListHref({
            page: 1,
            perPage: postsPagination.perPage,
            search: '',
            title: postsFilters.title,
            status: postsFilters.status,
            publishedAt: postsFilters.publishedAt,
            updatedAt: postsFilters.updatedAt,
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    useEffect(() => {
        if (debouncedSearch === postsFilters.search.trim()) {
            return;
        }

        const href = buildPostsListHref({
            page: 1,
            perPage: postsPagination.perPage,
            search: debouncedSearch,
            title: postsFilters.title,
            status: postsFilters.status,
            publishedAt: postsFilters.publishedAt,
            updatedAt: postsFilters.updatedAt,
            sortBy: postsSorting.sortBy,
            sortDirection: postsSorting.sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    }, [
        debouncedSearch,
        postsFilters.publishedAt,
        postsFilters.search,
        postsFilters.status,
        postsFilters.title,
        postsFilters.updatedAt,
        postsPagination.perPage,
        postsSorting.sortBy,
        postsSorting.sortDirection,
        buildPostsListHref,
    ]);

    const handleSortingChange = (updater: Updater<SortingState>) => {
        const nextSorting =
            typeof updater === 'function' ? updater(sorting) : updater;
        const firstSort = nextSorting[0];

        const sortBy = firstSort?.id ?? 'id';
        const sortDirection = firstSort
            ? firstSort.desc
                ? 'desc'
                : 'asc'
            : 'desc';

        if (
            sortBy === postsSorting.sortBy &&
            sortDirection === postsSorting.sortDirection
        ) {
            return;
        }

        const href = buildPostsListHref({
            page: 1,
            perPage: postsPagination.perPage,
            search: postsFilters.search,
            title: postsFilters.title,
            status: postsFilters.status,
            publishedAt: postsFilters.publishedAt,
            updatedAt: postsFilters.updatedAt,
            sortBy,
            sortDirection,
        });

        if (!href) {
            return;
        }

        router.get(href, {}, { preserveScroll: true });
    };

    const table = useReactTable({
        data: posts,
        columns,
        state: { pagination, sorting },
        onPaginationChange: handlePaginationChange,
        onSortingChange: handleSortingChange,
        manualPagination: true,
        manualSorting: true,
        rowCount: postsPagination.total,
        pageCount: postsPagination.lastPage,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
    });

    return (
        <Table.Card className="border-sidebar-border/70 dark:border-sidebar-border">
            <Table.Toolbar className="border-sidebar-border/70 dark:border-sidebar-border">
                <PostsListToolbar
                    currentTeam={currentTeam}
                    currentOrg={currentOrg}
                    activeType={activeType}
                    postTypes={postTypes}
                    postTypeUi={postTypeUi}
                    query={currentQuery}
                    searchVisible={visibleBlock === 'search'}
                    filtersVisible={visibleBlock === 'filters'}
                    hasActiveSearch={hasActiveSearch}
                    hasActiveColumnFilters={hasActiveColumnFilters}
                    onToggleSearch={() =>
                        setVisibleBlock((prevVisibleBlock) =>
                            prevVisibleBlock === 'search' ? null : 'search',
                        )
                    }
                    onToggleFilters={() =>
                        setVisibleBlock((prevVisibleBlock) =>
                            prevVisibleBlock === 'filters' ? null : 'filters',
                        )
                    }
                />
            </Table.Toolbar>

            {visibleBlock && (
                <>
                    <Table.Toolbar className="border-sidebar-border/70 dark:border-sidebar-border">
                        {visibleBlock === 'search' ? (
                            <div className="flex w-full items-center gap-2">
                                <Input
                                    value={filterSearch}
                                    onChange={(event) =>
                                        setFilterSearch(event.target.value)
                                    }
                                    placeholder="Поиск по строке"
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={handleSearchReset}
                                >
                                    Сбросить
                                </Button>
                            </div>
                        ) : null}

                        {visibleBlock === 'filters' ? (
                            <form
                                onSubmit={handleFiltersSubmit}
                                className="grid w-full grid-cols-1 gap-3 md:grid-cols-5"
                            >
                                <Input
                                    value={filterTitle}
                                    onChange={(event) =>
                                        setFilterTitle(event.target.value)
                                    }
                                    placeholder="Фильтр: заголовок"
                                />
                                <Input
                                    value={filterStatus}
                                    onChange={(event) =>
                                        setFilterStatus(event.target.value)
                                    }
                                    placeholder="Фильтр: статус"
                                />
                                <Input
                                    type="date"
                                    value={filterPublishedAt}
                                    onChange={(event) =>
                                        setFilterPublishedAt(event.target.value)
                                    }
                                />
                                <Input
                                    type="date"
                                    value={filterUpdatedAt}
                                    onChange={(event) =>
                                        setFilterUpdatedAt(event.target.value)
                                    }
                                />
                                <div className="flex items-center gap-2">
                                    <Button type="submit" size="sm">
                                        Применить
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={handleFiltersReset}
                                    >
                                        Сбросить
                                    </Button>
                                </div>
                            </form>
                        ) : null}
                    </Table.Toolbar>
                </>
            )}

            <Table.ScrollArea>
                <Table>
                    <Table.Header>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <Table.Row key={headerGroup.id} header>
                                {headerGroup.headers.map((header) => (
                                    <Table.Head
                                        key={header.id}
                                        variant={tableColumnVariant(
                                            header.column.id,
                                        )}
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </Table.Head>
                                ))}
                            </Table.Row>
                        ))}
                    </Table.Header>
                    <Table.Body>
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <Table.Row key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <Table.Cell
                                            key={cell.id}
                                            variant={tableColumnVariant(
                                                cell.column.id,
                                            )}
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </Table.Cell>
                                    ))}
                                </Table.Row>
                            ))
                        ) : (
                            <Table.Row>
                                <Table.Cell
                                    colSpan={columns.length}
                                    className="py-8 text-center text-sm text-muted-foreground"
                                >
                                    Для типа "{activeType}" записей пока нет.
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table>
            </Table.ScrollArea>

            <div className="px-4 pb-4">
                <TablePagination
                    table={table}
                    totalRowCount={postsPagination.total}
                    pageSizeOptions={[10, 25, 50]}
                    onPageSizeChange={handlePerPageChange}
                />
            </div>
        </Table.Card>
    );
}

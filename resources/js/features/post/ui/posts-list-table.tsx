import {
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { useMemo } from 'react';
import type { PostListRow } from '@/entities/post';
import { PostStatusCell, PostTitleSlugCell } from '@/entities/post';
import { Table } from '@/shared/ui/table';
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
>;

export function PostsListTable({
    posts,
    activeType,
    currentTeam,
    currentOrg,
    postTypes,
    postTypeUi,
}: Props) {
    const columns = useMemo(
        () => [
            columnHelper.accessor('title', {
                header: 'Заголовок',
                cell: ({ row }) => (
                    <PostTitleSlugCell
                        title={row.original.title}
                        slug={row.original.slug}
                    />
                ),
            }),
            columnHelper.accessor('status', {
                header: 'Статус',
                cell: ({ getValue }) => (
                    <PostStatusCell status={getValue()} />
                ),
            }),
            columnHelper.accessor('published_at', {
                header: 'Публикация',
                cell: ({ getValue }) =>
                    getValue()
                        ? new Date(getValue() as string).toLocaleString('ru-RU')
                        : '—',
            }),
            columnHelper.accessor('updated_at', {
                header: 'Обновлено',
                cell: ({ getValue }) =>
                    getValue()
                        ? new Date(getValue() as string).toLocaleString('ru-RU')
                        : '—',
            }),
        ],
        [],
    );

    const table = useReactTable({
        data: posts,
        columns,
        getCoreRowModel: getCoreRowModel(),
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
                />
            </Table.Toolbar>

            <Table.ScrollArea>
                <Table>
                    <Table.Header>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <Table.Row key={headerGroup.id} header>
                                {headerGroup.headers.map((header) => (
                                    <Table.Head key={header.id}>
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
                                        <Table.Cell key={cell.id}>
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
        </Table.Card>
    );
}

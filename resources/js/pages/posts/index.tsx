import { Head } from '@inertiajs/react';
import {
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { useMemo } from 'react';
import type { PostListRow } from '@/entities/post';
import { PostStatusCell, PostTitleSlugCell } from '@/entities/post';
import { PostsListToolbar, usePostsListPage } from '@/features/post';
import { dashboard } from '@/routes';
import { Table } from '@/shared/ui/table';

const columnHelper = createColumnHelper<PostListRow>();

export default function PostsIndex() {
    const { props } = usePostsListPage();
    const { currentTeam, currentOrg, activeType, postTypeUi, postTypes, posts } = props;

    const columns = useMemo(
        () => [
            columnHelper.accessor('title', {
                header: 'Заголовок',
                cell: ({ row }) => (
                    <PostTitleSlugCell title={row.original.title} slug={row.original.slug} />
                ),
            }),
            columnHelper.accessor('status', {
                header: 'Статус',
                cell: ({ getValue }) => <PostStatusCell status={getValue()} />,
            }),
            columnHelper.accessor('published_at', {
                header: 'Публикация',
                cell: ({ getValue }) =>
                    getValue() ? new Date(getValue() as string).toLocaleString('ru-RU') : '—',
            }),
            columnHelper.accessor('updated_at', {
                header: 'Обновлено',
                cell: ({ getValue }) =>
                    getValue() ? new Date(getValue() as string).toLocaleString('ru-RU') : '—',
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
        <>
            <Head title="Записи" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-2xl font-semibold">Записи</h1>
                    <p className="mt-2 text-sm text-muted-foreground">Управление записями текущей организации.</p>
                </div>

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
                                                          header.column.columnDef.header,
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
                                                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                                </Table.Cell>
                                            ))}
                                        </Table.Row>
                                    ))
                                ) : (
                                    <Table.Row>
                                        <Table.Cell colSpan={columns.length} className="py-8 text-center text-sm text-muted-foreground">
                                            Для типа "{activeType}" записей пока нет.
                                        </Table.Cell>
                                    </Table.Row>
                                )}
                            </Table.Body>
                        </Table>
                    </Table.ScrollArea>
                </Table.Card>
            </div>
        </>
    );
}

PostsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
    currentOrg?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Главная',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Записи',
            href:
                props.currentTeam && props.currentOrg
                    ? `/${props.currentTeam.slug}/${props.currentOrg.slug}/posts/page`
                    : '/posts',
        },
    ],
});

import { Head, Link, usePage } from '@inertiajs/react';
import {
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { useMemo } from 'react';
import { Table } from '@/components/table';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type PostRow = {
    id: number;
    type: string;
    status: string;
    slug: string;
    title: string;
    published_at: string | null;
    updated_at: string | null;
};

type PostsPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    postTypes: string[];
    posts: PostRow[];
};

const columnHelper = createColumnHelper<PostRow>();

export default function PostsIndex() {
    const page = usePage<PostsPageProps>();
    const currentTeam = page.props.currentTeam;
    const currentOrg = page.props.currentOrg;
    const activeType = page.props.activeType;
    const postTypes = page.props.postTypes;
    const posts = page.props.posts;

    const columns = useMemo(
        () => [
            columnHelper.accessor('title', {
                header: 'Заголовок',
                cell: ({ row }) => (
                    <div className="flex flex-col gap-1">
                        <span className="font-medium text-foreground">{row.original.title}</span>
                        <span className="text-xs text-muted-foreground">/{row.original.slug}</span>
                    </div>
                ),
            }),
            columnHelper.accessor('status', {
                header: 'Статус',
                cell: ({ getValue }) => (
                    <span className="inline-flex rounded-md border px-2 py-0.5 text-xs">
                        {getValue()}
                    </span>
                ),
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

    const buildTypeUrl = (type: string): string => {
        if (!currentTeam || !currentOrg) {
            return '/posts';
        }

        return `/${currentTeam.slug}/${currentOrg.slug}/posts/${type}`;
    };

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
                        <div className="flex flex-wrap items-center gap-2">
                            {postTypes.map((type) => {
                                const isActive = type === activeType;

                                return (
                                    <Button key={type} variant={isActive ? 'default' : 'outline'} size="sm" asChild>
                                        <Link href={buildTypeUrl(type)}>{type}</Link>
                                    </Button>
                                );
                            })}
                        </div>
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

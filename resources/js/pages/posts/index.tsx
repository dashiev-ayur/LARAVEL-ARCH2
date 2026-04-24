import { Head, Link, usePage } from '@inertiajs/react';
import {
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { useMemo } from 'react';
import type { PostListRow } from '@/entities/post';
import { PostStatusCell, PostTitleSlugCell } from '@/entities/post';
import { ButtonNewPost } from '@/features/post';
import { dashboard } from '@/routes';
import { Button } from '@/shared/ui/button';
import { Table } from '@/shared/ui/table';

type PostTypeUiItem = {
    filterButtonTitle: string;
    newButtonTitle: string;
};

type PostsPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    /** Код активного типа из контроллера/URL (как `PostType` на бэке). */
    activeType: string;
    postTypeUi: Record<string, PostTypeUiItem>;
    /** Порядок и набор кодов — `PostType::values()` на бэке; union на фронте не дублируем. */
    postTypes: readonly string[];
    posts: PostListRow[];
};

const columnHelper = createColumnHelper<PostListRow>();

export default function PostsIndex() {
    const page = usePage<PostsPageProps>();
    const currentTeam = page.props.currentTeam;
    const currentOrg = page.props.currentOrg;
    const activeType = page.props.activeType;
    const postTypeUi = page.props.postTypeUi;
    const postTypes = page.props.postTypes;
    const posts = page.props.posts;

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
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            {postTypes.map((type) => {
                                const isActive = type === activeType;
                                const label = postTypeUi[type]?.filterButtonTitle ?? type;

                                return (
                                    <Button key={type} variant={isActive ? 'default' : 'outline'} size="sm" asChild>
                                        <Link href={buildTypeUrl(type)}>{label}</Link>
                                    </Button>
                                );
                            })}
                        </div>
                        <ButtonNewPost
                            className="shrink-0"
                            newButtonTitle={postTypeUi[activeType]?.newButtonTitle ?? 'Новая запись'}
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

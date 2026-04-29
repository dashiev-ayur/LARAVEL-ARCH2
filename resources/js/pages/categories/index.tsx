import { Head, Link, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import type { CategoryListRow } from '@/entities/category';
import { CreateCategoryDialog } from '@/features/category';
import { dashboard } from '@/routes';
import { byType, index as categoriesIndex } from '@/routes/categories';
import { formatDateTime } from '@/shared/lib/format-date-time';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Table, tableColumnVariant } from '@/shared/ui/table';

type PostTypeUiItem = {
    filterButtonTitle: string;
    newButtonTitle: string;
};

type CategoriesListPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    categories: CategoryListRow[];
    postTypes: readonly string[];
    postTypeUi: Record<string, PostTypeUiItem>;
};

function buildCategoryTypeHref(
    currentTeam: { slug: string } | null,
    currentOrg: { slug: string } | null,
    type: string,
): string {
    if (!currentTeam || !currentOrg) {
        return '/categories';
    }

    if (type === 'page') {
        return categoriesIndex.url({
            current_team: currentTeam.slug,
            current_org: currentOrg.slug,
        });
    }

    return byType.url({
        current_team: currentTeam.slug,
        current_org: currentOrg.slug,
        type,
    });
}

export default function CategoriesIndex() {
    const { props } = usePage<CategoriesListPageProps>();
    const { currentTeam, currentOrg, activeType, postTypes, postTypeUi } =
        props;

    return (
        <>
            <Head title="Категории" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-2xl font-semibold">Категории</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Древовидный список категорий текущей организации.
                    </p>
                </div>

                <Table.Card className="border-sidebar-border/70 dark:border-sidebar-border">
                    <Table.Toolbar className="border-sidebar-border/70 dark:border-sidebar-border">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            {postTypes.map((type) => {
                                const isActive = type === activeType;
                                const label =
                                    postTypeUi[type]?.filterButtonTitle ?? type;

                                return (
                                    <Button
                                        key={type}
                                        variant={
                                            isActive ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        asChild
                                    >
                                        <Link
                                            href={buildCategoryTypeHref(
                                                currentTeam,
                                                currentOrg,
                                                type,
                                            )}
                                        >
                                            {label}
                                        </Link>
                                    </Button>
                                );
                            })}
                        </div>
                        <CreateCategoryDialog
                            className="shrink-0"
                            currentTeam={currentTeam}
                            currentOrg={currentOrg}
                            activeType={activeType}
                            categories={props.categories}
                        />
                    </Table.Toolbar>

                    <Table.ScrollArea>
                        <Table>
                            <Table.Header>
                                <Table.Row header>
                                    <Table.Head variant="select">
                                        <span className="sr-only">Выбор</span>
                                    </Table.Head>
                                    <Table.Head
                                        variant={tableColumnVariant('title')}
                                    >
                                        Название
                                    </Table.Head>
                                    <Table.Head
                                        variant={tableColumnVariant('slug')}
                                    >
                                        Slug
                                    </Table.Head>
                                    <Table.Head
                                        variant={tableColumnVariant('type')}
                                    >
                                        Тип
                                    </Table.Head>
                                    <Table.Head
                                        variant={tableColumnVariant(
                                            'updated_at',
                                        )}
                                    >
                                        Обновлено
                                    </Table.Head>
                                    <Table.Head variant="actions">
                                        <span className="sr-only">
                                            Действия
                                        </span>
                                    </Table.Head>
                                </Table.Row>
                            </Table.Header>
                            <Table.Body>
                                {props.categories.length > 0 ? (
                                    props.categories.map((category) => (
                                        <Table.Row key={category.id}>
                                            <Table.Cell variant="select">
                                                <Checkbox
                                                    aria-label={`Выбрать категорию ${category.title}`}
                                                />
                                            </Table.Cell>
                                            <Table.Cell
                                                variant={tableColumnVariant(
                                                    'title',
                                                )}
                                            >
                                                <div
                                                    className="flex items-center gap-2"
                                                    style={{
                                                        paddingInlineStart: `${category.depth * 1.5}rem`,
                                                    }}
                                                >
                                                    {category.depth > 0 ? (
                                                        <span className="text-muted-foreground">
                                                            --
                                                        </span>
                                                    ) : null}
                                                    <span className="font-medium">
                                                        {category.title}
                                                    </span>
                                                </div>
                                            </Table.Cell>
                                            <Table.Cell
                                                variant={tableColumnVariant(
                                                    'slug',
                                                )}
                                                className="font-mono text-xs text-muted-foreground"
                                            >
                                                {category.slug}
                                            </Table.Cell>
                                            <Table.Cell
                                                variant={tableColumnVariant(
                                                    'type',
                                                )}
                                            >
                                                {category.type}
                                            </Table.Cell>
                                            <Table.Cell
                                                variant={tableColumnVariant(
                                                    'updated_at',
                                                )}
                                            >
                                                {formatDateTime(
                                                    category.updated_at,
                                                )}
                                            </Table.Cell>
                                            <Table.Cell variant="actions">
                                                <CreateCategoryDialog
                                                    currentTeam={currentTeam}
                                                    currentOrg={currentOrg}
                                                    activeType={activeType}
                                                    categories={
                                                        props.categories
                                                    }
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
                                    ))
                                ) : (
                                    <Table.Row>
                                        <Table.Cell
                                            colSpan={6}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Категорий пока нет.
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

CategoriesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
    currentOrg?: { slug: string } | null;
    activeType?: string;
}) => ({
    breadcrumbs: [
        {
            title: 'Главная',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Категории',
            href:
                props.currentTeam && props.currentOrg
                    ? buildCategoryTypeHref(
                          props.currentTeam,
                          props.currentOrg,
                          props.activeType ?? 'page',
                      )
                    : '/categories',
        },
    ],
});

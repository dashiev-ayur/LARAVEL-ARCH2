import { Head, Link, usePage } from '@inertiajs/react';
import type { CategoryListRow } from '@/entities/category';
import { CategoriesTreeTable, CreateCategoryDialog } from '@/features/category';
import { dashboard } from '@/routes';
import { byType, index as categoriesIndex } from '@/routes/categories';
import { Button } from '@/shared/ui/button';
import { Table } from '@/shared/ui/table';

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

                    <CategoriesTreeTable
                        currentTeam={currentTeam}
                        currentOrg={currentOrg}
                        activeType={activeType}
                        categories={props.categories}
                    />
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

import { Head, usePage } from '@inertiajs/react';
import type { PageListRow, PageStatus } from '@/entities/page';
import { PageDialog, StructureTreeTable } from '@/features/structure';
import { dashboard } from '@/routes';
import { index as pagesIndex } from '@/routes/pages';
import { Table } from '@/shared/ui/table';

type StructureIndexPageProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    pages: PageListRow[];
    pageStatuses: readonly PageStatus[];
};

function buildStructureHref(
    currentTeam: { slug: string } | null,
    currentOrg: { slug: string } | null,
): string {
    if (!currentTeam || !currentOrg) {
        return '/structure';
    }

    return pagesIndex.url({
        current_team: currentTeam.slug,
        current_org: currentOrg.slug,
    });
}

export default function StructureIndex() {
    const { props } = usePage<StructureIndexPageProps>();
    const { currentTeam, currentOrg, pages, pageStatuses } = props;

    return (
        <>
            <Head title="Структура сайта" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-2xl font-semibold">Структура сайта</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Древовидная структура страниц текущей организации.
                    </p>
                </div>

                <Table.Card className="border-sidebar-border/70 dark:border-sidebar-border">
                    <Table.Toolbar className="border-sidebar-border/70 dark:border-sidebar-border">
                        <div className="text-sm text-muted-foreground">
                            {pages.length > 0
                                ? `Страниц: ${pages.length}`
                                : 'Структура сайта пока пуста'}
                        </div>
                        <PageDialog
                            className="shrink-0"
                            currentTeam={currentTeam}
                            currentOrg={currentOrg}
                            pages={pages}
                            pageStatuses={pageStatuses}
                        />
                    </Table.Toolbar>

                    <StructureTreeTable
                        currentTeam={currentTeam}
                        currentOrg={currentOrg}
                        pages={pages}
                        pageStatuses={pageStatuses}
                    />
                </Table.Card>
            </div>
        </>
    );
}

StructureIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
    currentOrg?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Главная',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Структура сайта',
            href: buildStructureHref(
                props.currentTeam ?? null,
                props.currentOrg ?? null,
            ),
        },
    ],
});

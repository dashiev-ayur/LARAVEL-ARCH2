import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { index as pagesIndex } from '@/routes/pages';

export default function PagesIndex() {
    return (
        <>
            <Head title="Страницы" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-2xl font-semibold">Страницы</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Раздел в разработке.
                    </p>
                </div>
            </div>
        </>
    );
}

PagesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
    currentOrg?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Главная',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Страницы',
            href:
                props.currentTeam && props.currentOrg
                    ? pagesIndex.url({
                          current_team: props.currentTeam.slug,
                          current_org: props.currentOrg.slug,
                      })
                    : '/pages',
        },
    ],
});

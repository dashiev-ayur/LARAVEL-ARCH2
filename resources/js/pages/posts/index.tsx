import { Head } from '@inertiajs/react';
import { PostsListTable, usePostsListPage } from '@/features/post';
import { dashboard } from '@/routes';
import { index as postsIndex } from '@/routes/posts';

export default function PostsIndex() {
    const { props } = usePostsListPage();
    const {
        currentTeam,
        currentOrg,
        activeType,
        postTypeUi,
        postTypes,
        posts,
        postsPagination,
        postsFilters,
        postsSorting,
    } = props;

    return (
        <>
            <Head title="Записи" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-2xl font-semibold">Записи</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Управление записями текущей организации.
                    </p>
                </div>

                <PostsListTable
                    posts={posts}
                    activeType={activeType}
                    currentTeam={currentTeam}
                    currentOrg={currentOrg}
                    postTypes={postTypes}
                    postTypeUi={postTypeUi}
                    postsPagination={postsPagination}
                    postsFilters={postsFilters}
                    postsSorting={postsSorting}
                />
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
                    ? postsIndex.url({
                          current_team: props.currentTeam.slug,
                          current_org: props.currentOrg.slug,
                      })
                    : '/posts',
        },
    ],
});

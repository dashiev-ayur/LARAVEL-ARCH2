import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PostFormFields } from '@/features/post';
import type { PostEditPageProps, PostsListQuery } from '@/features/post';
import { dashboard } from '@/routes';
import {
    byType,
    create as createPost,
    destroy as destroyPost,
    edit as editPost,
    index as postsIndex,
    store as storePost,
    update as updatePost,
} from '@/routes/posts';
import { Button } from '@/shared/ui/button';

type PostsListRouteQuery = {
    page: number;
    per_page: number;
    search?: string;
    filter_title?: string;
    filter_status?: string;
    filter_published_at?: string;
    filter_updated_at?: string;
    sort_by: string;
    sort_direction: string;
};

export default function PostEdit() {
    const { props } = usePage<PostEditPageProps>();
    const {
        currentTeam,
        currentOrg,
        activeType,
        post,
        postsListQuery,
        postTypeUi,
    } = props;
    const [deleting, setDeleting] = useState(false);
    const isEditing = post !== null;
    const canDelete = post?.status === 'draft';
    const postTypeTitle = postTypeUi[activeType]?.filterButtonTitle ?? 'Записи';
    const pageTitle = isEditing
        ? `Редактировать: ${post.title}`
        : (postTypeUi[activeType]?.newButtonTitle ?? 'Новая запись');
    const listHref = useMemo(
        () =>
            buildPostsListHref(
                currentTeam,
                currentOrg,
                activeType,
                postsListQuery,
            ),
        [activeType, currentOrg, currentTeam, postsListQuery],
    );
    const isDisabled = !currentTeam || !currentOrg;
    const formProps =
        isEditing && post
            ? updatePost.form(
                  {
                      current_team: currentTeam?.slug ?? '',
                      current_org: currentOrg?.slug ?? '',
                      post: post.id,
                  },
                  {
                      query: toPostsListRouteQuery(postsListQuery),
                  },
              )
            : storePost.form(
                  {
                      current_team: currentTeam?.slug ?? '',
                      current_org: currentOrg?.slug ?? '',
                  },
                  {
                      query: toPostsListRouteQuery(postsListQuery),
                  },
              );

    const handleDelete = () => {
        if (!currentTeam || !currentOrg || !post || !canDelete) {
            return;
        }

        router.delete(
            destroyPost.url({
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
                post: post.id,
            }),
            {
                preserveScroll: true,
                onStart: () => setDeleting(true),
                onFinish: () => setDeleting(false),
            },
        );
    };

    return (
        <>
            <Head title={pageTitle} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                {postTypeTitle}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {isEditing
                                    ? 'Редактировать запись'
                                    : 'Создать запись'}
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {isEditing
                                    ? 'Обновите основные поля записи. Если очистить slug, он будет создан заново.'
                                    : 'Заполните основные поля записи. Slug можно оставить пустым, тогда он будет создан автоматически.'}
                            </p>
                        </div>

                        <Button variant="outline" asChild>
                            <Link href={listHref}>Назад к списку</Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <Form {...formProps} className="space-y-6">
                        {({ errors, processing }) => (
                            <>
                                <PostFormFields
                                    activeType={activeType}
                                    post={post ?? undefined}
                                    errors={errors}
                                />

                                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                                    {isEditing ? (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            disabled={
                                                isDisabled ||
                                                !canDelete ||
                                                processing ||
                                                deleting
                                            }
                                            title={
                                                canDelete
                                                    ? 'Удалить черновик'
                                                    : 'Удалять можно только черновики'
                                            }
                                            onClick={handleDelete}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            {deleting
                                                ? 'Удаление...'
                                                : 'Удалить'}
                                        </Button>
                                    ) : (
                                        <span />
                                    )}

                                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            asChild
                                        >
                                            <Link href={listHref}>Отмена</Link>
                                        </Button>

                                        <Button
                                            type="submit"
                                            data-test="post-submit"
                                            disabled={processing || deleting}
                                        >
                                            {processing
                                                ? isEditing
                                                    ? 'Сохранение...'
                                                    : 'Создание...'
                                                : isEditing
                                                  ? 'Сохранить'
                                                  : 'Создать'}
                                        </Button>
                                    </div>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

PostEdit.layout = (props: PostEditPageProps) => ({
    breadcrumbs: [
        {
            title: 'Главная',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Записи',
            href: buildPostsListHref(
                props.currentTeam,
                props.currentOrg,
                props.activeType,
                props.postsListQuery,
            ),
        },
        {
            title:
                props.post?.title ??
                props.postTypeUi[props.activeType]?.newButtonTitle ??
                'Новая запись',
            href:
                props.currentTeam && props.currentOrg
                    ? props.post
                        ? editPost.url(
                              {
                                  current_team: props.currentTeam.slug,
                                  current_org: props.currentOrg.slug,
                                  post: props.post.id,
                              },
                              {
                                  query: toPostsListRouteQuery(
                                      props.postsListQuery,
                                  ),
                              },
                          )
                        : createPost.url(
                              {
                                  current_team: props.currentTeam.slug,
                                  current_org: props.currentOrg.slug,
                              },
                              {
                                  query: toCreateRouteQuery(
                                      props.activeType,
                                      props.postsListQuery,
                                  ),
                              },
                          )
                    : buildPostsListHref(
                          props.currentTeam,
                          props.currentOrg,
                          props.activeType,
                          props.postsListQuery,
                      ),
        },
    ],
});

function buildPostsListHref(
    currentTeam: { slug: string } | null,
    currentOrg: { slug: string } | null,
    activeType: string,
    postsListQuery: PostsListQuery,
): string {
    if (!currentTeam || !currentOrg) {
        return '/posts';
    }

    if (activeType === 'page') {
        return postsIndex.url(
            {
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
            },
            {
                query: toPostsListRouteQuery(postsListQuery),
            },
        );
    }

    return byType.url(
        {
            current_team: currentTeam.slug,
            current_org: currentOrg.slug,
            type: activeType,
        },
        {
            query: toPostsListRouteQuery(postsListQuery),
        },
    );
}

function toPostsListRouteQuery(
    postsListQuery: PostsListQuery,
): PostsListRouteQuery {
    return {
        page: postsListQuery.page,
        per_page: postsListQuery.per_page,
        search: postsListQuery.search || undefined,
        filter_title: postsListQuery.filter_title || undefined,
        filter_status: postsListQuery.filter_status || undefined,
        filter_published_at: postsListQuery.filter_published_at || undefined,
        filter_updated_at: postsListQuery.filter_updated_at || undefined,
        sort_by: postsListQuery.sort_by,
        sort_direction: postsListQuery.sort_direction,
    };
}

function toCreateRouteQuery(
    activeType: string,
    postsListQuery: PostsListQuery,
): PostsListRouteQuery & { type: string } {
    return {
        type: activeType,
        ...toPostsListRouteQuery(postsListQuery),
    };
}

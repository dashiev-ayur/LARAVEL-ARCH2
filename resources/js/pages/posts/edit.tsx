import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PostFormFields } from '@/features/post';
import type { PostEditPageProps } from '@/features/post';
import { dashboard } from '@/routes';
import {
    byType,
    destroy as destroyPost,
    edit as editPost,
    index as postsIndex,
    update as updatePost,
} from '@/routes/posts';
import { Button } from '@/shared/ui/button';

export default function PostEdit() {
    const { props } = usePage<PostEditPageProps>();
    const { currentTeam, currentOrg, activeType, post, postTypeUi } = props;
    const [deleting, setDeleting] = useState(false);
    const canDelete = post.status === 'draft';
    const postTypeTitle = postTypeUi[activeType]?.filterButtonTitle ?? 'Записи';
    const listHref = useMemo(
        () => buildPostsListHref(currentTeam, currentOrg, activeType),
        [activeType, currentOrg, currentTeam],
    );
    const isDisabled = !currentTeam || !currentOrg;

    const handleDelete = () => {
        if (!currentTeam || !currentOrg || !canDelete) {
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
            <Head title={`Редактировать: ${post.title}`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                {postTypeTitle}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                Редактировать запись
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Обновите основные поля записи. Если очистить
                                slug, он будет создан заново.
                            </p>
                        </div>

                        <Button variant="outline" asChild>
                            <Link href={listHref}>Назад к списку</Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <Form
                        {...updatePost.form({
                            current_team: currentTeam?.slug ?? '',
                            current_org: currentOrg?.slug ?? '',
                            post: post.id,
                        })}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <PostFormFields
                                    activeType={activeType}
                                    post={post}
                                    errors={errors}
                                />

                                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
                                        {deleting ? 'Удаление...' : 'Удалить'}
                                    </Button>

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
                                                ? 'Сохранение...'
                                                : 'Сохранить'}
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
            ),
        },
        {
            title: props.post.title,
            href:
                props.currentTeam && props.currentOrg
                    ? editPost.url({
                          current_team: props.currentTeam.slug,
                          current_org: props.currentOrg.slug,
                          post: props.post.id,
                      })
                    : buildPostsListHref(
                          props.currentTeam,
                          props.currentOrg,
                          props.activeType,
                      ),
        },
    ],
});

function buildPostsListHref(
    currentTeam: { slug: string } | null,
    currentOrg: { slug: string } | null,
    activeType: string,
): string {
    if (!currentTeam || !currentOrg) {
        return '/posts';
    }

    if (activeType === 'page') {
        return postsIndex.url({
            current_team: currentTeam.slug,
            current_org: currentOrg.slug,
        });
    }

    return byType.url({
        current_team: currentTeam.slug,
        current_org: currentOrg.slug,
        type: activeType,
    });
}

import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { PostFormFields } from '@/features/post';
import type {
    PostCategoryRelationRow,
    PostEditPageProps,
    PostsListQuery,
} from '@/features/post';
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
import { update as updatePostCategories } from '@/routes/posts/categories';
import { useDebounce } from '@/shared/lib/hooks/use-debounce';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Table } from '@/shared/ui/table';

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
        categories,
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

                <div className="flex flex-col gap-4 xl:flex-row xl:items-start">
                    <div className="rounded-xl border border-sidebar-border/70 p-6 xl:flex-1 dark:border-sidebar-border">
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
                                                <Link href={listHref}>
                                                    Отмена
                                                </Link>
                                            </Button>

                                            <Button
                                                type="submit"
                                                data-test="post-submit"
                                                disabled={
                                                    processing || deleting
                                                }
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

                    <PostCategoriesPanel
                        categories={categories}
                        currentTeam={currentTeam}
                        currentOrg={currentOrg}
                        post={post}
                        postsListQuery={postsListQuery}
                    />
                </div>
            </div>
        </>
    );
}

type PostCategoriesPanelProps = {
    categories: PostCategoryRelationRow[];
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    post: PostEditPageProps['post'];
    postsListQuery: PostsListQuery;
};

function PostCategoriesPanel({
    categories,
    currentTeam,
    currentOrg,
    post,
    postsListQuery,
}: PostCategoriesPanelProps) {
    const initialCategoryIds = useMemo(
        () => getLinkedCategoryIds(categories),
        [categories],
    );
    const [selectedCategoryIds, setSelectedCategoryIds] =
        useState<number[]>(initialCategoryIds);
    const [saving, setSaving] = useState(false);
    const lastSavedSignatureRef = useRef(
        categoryIdsSignature(initialCategoryIds),
    );
    const debouncedCategoryIds = useDebounce(selectedCategoryIds, 1000);
    const canSave = Boolean(currentTeam && currentOrg && post);

    useEffect(() => {
        const serverCategoryIds = getLinkedCategoryIds(categories);
        const serverSignature = categoryIdsSignature(serverCategoryIds);

        lastSavedSignatureRef.current = serverSignature;
        setSelectedCategoryIds(serverCategoryIds);
    }, [categories]);

    useEffect(() => {
        if (!canSave || !currentTeam || !currentOrg || !post) {
            return;
        }

        const categoryIds = normalizeCategoryIds(debouncedCategoryIds);
        const categoryIdsRequestSignature = categoryIdsSignature(categoryIds);

        if (categoryIdsRequestSignature === lastSavedSignatureRef.current) {
            return;
        }

        router.patch(
            updatePostCategories.url(
                {
                    current_team: currentTeam.slug,
                    current_org: currentOrg.slug,
                    post: post.id,
                },
                {
                    query: toPostsListRouteQuery(postsListQuery),
                },
            ),
            {
                category_ids: categoryIds,
            },
            {
                only: ['categories'],
                preserveScroll: true,
                onStart: () => setSaving(true),
                onSuccess: () => {
                    lastSavedSignatureRef.current = categoryIdsRequestSignature;
                },
                onFinish: () => setSaving(false),
            },
        );
    }, [
        canSave,
        currentOrg,
        currentTeam,
        debouncedCategoryIds,
        post,
        postsListQuery,
    ]);

    const handleCategoryChange = useCallback(
        (categoryId: number, checked: boolean) => {
            setSelectedCategoryIds((currentCategoryIds) => {
                const withoutCategory = currentCategoryIds.filter(
                    (currentCategoryId) => currentCategoryId !== categoryId,
                );

                return checked
                    ? normalizeCategoryIds([...withoutCategory, categoryId])
                    : withoutCategory;
            });
        },
        [],
    );

    return (
        <aside className="rounded-xl border border-sidebar-border/70 xl:w-[300px] xl:shrink-0 dark:border-sidebar-border">
            <div className="flex items-start justify-between p-4">
                <div>
                    <h2 className="text-lg font-semibold">Категории новости</h2>
                    <p className="text-sm text-muted-foreground">
                        Выберите категории для этой записи.
                    </p>
                </div>
            </div>

            {!post ? (
                <p className="mt-4 rounded-md border border-dashed border-sidebar-border/70 p-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    Сначала сохраните запись, затем выберите категории.
                </p>
            ) : categories.length > 0 ? (
                <div className="overflow-hidden rounded-none border-none">
                    <Table.ScrollArea className="rounded-none">
                        <Table className="w-full border-none">
                            <Table.Body className="border-none">
                                {categories.map((category) => (
                                    <Table.Row key={category.id}  className="border-t-1 border-b-0 border-sidebar-border/70">
                                        <Table.Cell variant="select">
                                            <Checkbox
                                                checked={selectedCategoryIds.includes(
                                                    category.id,
                                                )}
                                                disabled={!canSave || saving}
                                                aria-label={`Связать запись с категорией ${category.title}`}
                                                onCheckedChange={(checked) =>
                                                    handleCategoryChange(
                                                        category.id,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                        </Table.Cell>
                                        <Table.Cell className="pr-2">
                                            <div
                                                className="flex items-center gap-2"
                                                style={{
                                                    paddingInlineStart: `${category.depth * 1.25}rem`,
                                                }}
                                            >
                                                {category.depth > 0 ? (
                                                    <span className="text-muted-foreground">
                                                        --
                                                    </span>
                                                ) : null}
                                                <span className="font-medium break-words">
                                                    {category.title}
                                                </span>
                                            </div>
                                        </Table.Cell>
                                    </Table.Row>
                                ))}
                            </Table.Body>
                        </Table>
                    </Table.ScrollArea>
                </div>
            ) : (
                <p className="mt-4 rounded-md border border-dashed border-sidebar-border/70 p-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    Для этого типа записей пока нет категорий.
                </p>
            )}
        </aside>
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

function getLinkedCategoryIds(categories: PostCategoryRelationRow[]): number[] {
    return normalizeCategoryIds(
        categories
            .filter((category) => category.is_linked)
            .map((category) => category.id),
    );
}

function normalizeCategoryIds(categoryIds: number[]): number[] {
    return [...new Set(categoryIds)].sort((left, right) => left - right);
}

function categoryIdsSignature(categoryIds: number[]): string {
    return normalizeCategoryIds(categoryIds).join(',');
}

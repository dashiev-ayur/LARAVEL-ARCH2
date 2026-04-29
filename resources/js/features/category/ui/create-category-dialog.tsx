import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import type { CategoryListRow } from '@/entities/category';
import {
    destroy as destroyCategory,
    store as storeCategory,
    update as updateCategory,
} from '@/routes/categories';
import { Button } from '@/shared/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/shared/ui/dialog';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/ui/select';

type CreateCategoryDialogProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    categories: CategoryListRow[];
    category?: CategoryListRow;
    trigger?: ReactNode;
    className?: string;
};

const ROOT_PARENT_VALUE = 'root';

function getAvailableParentCategories(
    categories: CategoryListRow[],
    categoryType: string,
    category?: CategoryListRow,
): CategoryListRow[] {
    if (!category) {
        return categories.filter(
            (parentCategory) => parentCategory.type === categoryType,
        );
    }

    const childrenByParentId = new Map<number, CategoryListRow[]>();

    for (const parentCategory of categories) {
        if (parentCategory.parent_id === null) {
            continue;
        }

        childrenByParentId.set(parentCategory.parent_id, [
            ...(childrenByParentId.get(parentCategory.parent_id) ?? []),
            parentCategory,
        ]);
    }

    const excludedIds = new Set<number>([category.id]);
    const stack = [...(childrenByParentId.get(category.id) ?? [])];

    while (stack.length > 0) {
        const childCategory = stack.pop();

        if (!childCategory || excludedIds.has(childCategory.id)) {
            continue;
        }

        excludedIds.add(childCategory.id);
        stack.push(...(childrenByParentId.get(childCategory.id) ?? []));
    }

    return categories.filter(
        (parentCategory) =>
            parentCategory.type === categoryType &&
            !excludedIds.has(parentCategory.id),
    );
}

/**
 * Диалог создания или редактирования категории активного типа.
 */
export function CreateCategoryDialog({
    currentTeam,
    currentOrg,
    activeType,
    categories,
    category,
    trigger,
    className,
}: CreateCategoryDialogProps) {
    const isEditing = Boolean(category);
    const dialogTitle = isEditing
        ? 'Редактировать категорию'
        : 'Добавить категорию';
    const submitTitle = isEditing ? 'Сохранить' : 'Создать';
    const processingTitle = isEditing ? 'Сохранение...' : 'Создание...';
    const initialParentId = category?.parent_id
        ? String(category.parent_id)
        : ROOT_PARENT_VALUE;
    const [open, setOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [parentId, setParentId] = useState(initialParentId);
    const [deleting, setDeleting] = useState(false);
    const isDisabled = !currentTeam || !currentOrg;
    const categoryType = category?.type ?? activeType;
    const parentOptions = useMemo(
        () => getAvailableParentCategories(categories, categoryType, category),
        [categories, category, categoryType],
    );

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen) {
            setParentId(initialParentId);
            return;
        }

        setDeleteDialogOpen(false);
    };

    const formProps =
        isEditing && category
            ? updateCategory.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
                  category: category.id,
              })
            : storeCategory.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
              });

    const handleDelete = () => {
        if (!currentTeam || !currentOrg || !category) {
            return;
        }

        router.delete(
            destroyCategory.url({
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
                category: category.id,
            }),
            {
                preserveScroll: true,
                onStart: () => setDeleting(true),
                onFinish: () => setDeleting(false),
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    handleOpenChange(false);
                },
            },
        );
    };

    if (isDisabled) {
        return (
            <Button
                type="button"
                variant="outline"
                size="sm"
                className={className}
                disabled
            >
                Добавить категорию
            </Button>
        );
    }

    return (
        <>
            <Dialog open={open} onOpenChange={handleOpenChange}>
                <DialogTrigger asChild>
                    {trigger ?? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className={className}
                        >
                            Добавить категорию
                        </Button>
                    )}
                </DialogTrigger>
                <DialogContent className="sm:max-w-xl">
                    <Form
                        key={`${isEditing ? `edit-${category?.id}` : 'create'}-${String(open)}`}
                        {...formProps}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                        onSuccess={() => handleOpenChange(false)}
                        resetOnSuccess={!isEditing}
                    >
                        {({ errors, processing }) => (
                            <>
                                <DialogHeader>
                                    <DialogTitle>{dialogTitle}</DialogTitle>
                                    <DialogDescription>
                                        {isEditing
                                            ? 'Обновите название и slug категории. Если очистить slug, он будет создан заново.'
                                            : 'Заполните название категории. Slug можно оставить пустым, тогда он будет создан автоматически.'}
                                    </DialogDescription>
                                </DialogHeader>

                                <input
                                    type="hidden"
                                    name="type"
                                    value={category?.type ?? activeType}
                                />
                                <input
                                    type="hidden"
                                    name="parent_id"
                                    value={
                                        parentId === ROOT_PARENT_VALUE
                                            ? ''
                                            : parentId
                                    }
                                />

                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="category-title">
                                            Название
                                        </Label>
                                        <Input
                                            id="category-title"
                                            name="title"
                                            data-test="create-category-title"
                                            defaultValue={category?.title ?? ''}
                                            placeholder="Например, Документы"
                                            required
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="category-slug">
                                            Slug
                                        </Label>
                                        <Input
                                            id="category-slug"
                                            name="slug"
                                            data-test="create-category-slug"
                                            defaultValue={category?.slug ?? ''}
                                            placeholder="documents"
                                        />
                                        <InputError message={errors.slug} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="category-parent">
                                            Родительская категория
                                        </Label>
                                        <Select
                                            value={parentId}
                                            onValueChange={setParentId}
                                        >
                                            <SelectTrigger
                                                id="category-parent"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Выберите родительскую категорию" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    value={ROOT_PARENT_VALUE}
                                                >
                                                    Без родительской категории
                                                </SelectItem>
                                                {parentOptions.map(
                                                    (parentCategory) => (
                                                        <SelectItem
                                                            key={
                                                                parentCategory.id
                                                            }
                                                            value={String(
                                                                parentCategory.id,
                                                            )}
                                                        >
                                                            {'-- '.repeat(
                                                                parentCategory.depth,
                                                            )}
                                                            {
                                                                parentCategory.title
                                                            }
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.parent_id}
                                        />
                                    </div>
                                </div>

                                <DialogFooter className="gap-2 sm:justify-between">
                                    {isEditing ? (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            disabled={processing || deleting}
                                            onClick={() =>
                                                setDeleteDialogOpen(true)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Удалить
                                        </Button>
                                    ) : (
                                        <span />
                                    )}

                                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <DialogClose asChild>
                                            <Button
                                                type="button"
                                                variant="secondary"
                                            >
                                                Отмена
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            data-test="create-category-submit"
                                            disabled={processing || deleting}
                                        >
                                            {processing
                                                ? processingTitle
                                                : submitTitle}
                                        </Button>
                                    </div>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
            {category ? (
                <Dialog
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Удалить категорию?</DialogTitle>
                            <DialogDescription>
                                Категория <strong>{category.title}</strong>{' '}
                                будет удалена. Связи с записями будут удалены, а
                                прямые дочерние категории станут корневыми.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-3 rounded-lg border p-4 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Привязанные записи
                                </span>
                                <span className="font-medium">
                                    {category.posts_count}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Дочерние категории
                                </span>
                                <span className="font-medium">
                                    {category.children_count}
                                </span>
                            </div>
                        </div>

                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Отмена
                                </Button>
                            </DialogClose>

                            <Button
                                type="button"
                                variant="destructive"
                                disabled={deleting}
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4" />
                                {deleting ? 'Удаление...' : 'Удалить категорию'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </>
    );
}

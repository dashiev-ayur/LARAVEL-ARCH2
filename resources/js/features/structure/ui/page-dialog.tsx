import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import type { PageListRow, PageStatus } from '@/entities/page';
import {
    destroy as destroyPage,
    store as storePage,
    update as updatePage,
} from '@/routes/pages';
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
import { PageFormFields } from './page-form-fields';

type PageDialogProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    pages: PageListRow[];
    pageStatuses: readonly PageStatus[];
    page?: PageListRow;
    trigger?: ReactNode;
    className?: string;
};

/**
 * Диалог создания или редактирования страницы структуры сайта.
 */
export function PageDialog({
    currentTeam,
    currentOrg,
    pages,
    pageStatuses,
    page,
    trigger,
    className,
}: PageDialogProps) {
    const isEditing = Boolean(page);
    const dialogTitle = isEditing
        ? 'Редактировать страницу'
        : 'Создать страницу';
    const submitTitle = isEditing ? 'Сохранить' : 'Создать';
    const processingTitle = isEditing ? 'Сохранение...' : 'Создание...';
    const [open, setOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const isDisabled = !currentTeam || !currentOrg;
    const canDelete = isEditing && page && page.children_count === 0;

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setDeleteDialogOpen(false);
        }
    };

    const formProps =
        isEditing && page
            ? updatePage.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
                  page: page.id,
              })
            : storePage.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
              });

    const handleDelete = () => {
        if (!currentTeam || !currentOrg || !page) {
            return;
        }

        router.delete(
            destroyPage.url({
                current_team: currentTeam.slug,
                current_org: currentOrg.slug,
                page: page.id,
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
                Создать страницу
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
                            Создать страницу
                        </Button>
                    )}
                </DialogTrigger>
                <DialogContent className="sm:max-w-2xl">
                    <Form
                        key={`${isEditing ? `edit-${page?.id}` : 'create'}-${String(open)}`}
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
                                            ? 'Обновите основные поля страницы. Если очистить slug, он будет создан заново.'
                                            : 'Создайте корневую или дочернюю страницу. Slug можно оставить пустым.'}
                                    </DialogDescription>
                                </DialogHeader>

                                <PageFormFields
                                    pages={pages}
                                    page={page}
                                    pageStatuses={pageStatuses}
                                    errors={errors}
                                />
                                <InputError message={errors.page} />

                                <DialogFooter className="gap-2 sm:justify-between">
                                    {isEditing ? (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            disabled={
                                                processing ||
                                                deleting ||
                                                !canDelete
                                            }
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
                                            data-test="page-submit"
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
            {page ? (
                <Dialog
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Удалить страницу?</DialogTitle>
                            <DialogDescription>
                                Страница <strong>{page.title}</strong> будет
                                удалена. Страницы с дочерними узлами удалить
                                нельзя.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-3 rounded-lg border p-4 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Дочерние страницы
                                </span>
                                <span className="font-medium">
                                    {page.children_count}
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
                                disabled={deleting || !canDelete}
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4" />
                                {deleting ? 'Удаление...' : 'Удалить страницу'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </>
    );
}

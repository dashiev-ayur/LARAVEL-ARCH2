import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import type { PostListRow } from '@/entities/post';
import { store as storePost, update as updatePost } from '@/routes/posts';
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

type CreatePostDialogProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    newButtonTitle?: string;
    post?: PostListRow;
    trigger?: ReactNode;
    className?: string;
};

const POST_STATUS_OPTIONS = [
    { value: 'draft', label: 'Черновик' },
    { value: 'scheduled', label: 'Запланирована' },
    { value: 'published', label: 'Опубликована' },
    { value: 'archived', label: 'В архиве' },
] as const;

/**
 * Диалог создания или редактирования записи активного типа.
 */
export function CreatePostDialog({
    currentTeam,
    currentOrg,
    activeType,
    newButtonTitle = 'Новая запись',
    post,
    trigger,
    className,
}: CreatePostDialogProps) {
    const isEditing = Boolean(post);
    const dialogTitle = isEditing ? 'Редактировать запись' : newButtonTitle;
    const submitTitle = isEditing ? 'Сохранить' : 'Создать';
    const processingTitle = isEditing ? 'Сохранение...' : 'Создание...';
    const initialStatus = post?.status ?? 'draft';
    const [open, setOpen] = useState(false);
    const [status, setStatus] = useState(initialStatus);
    const isDisabled = !currentTeam || !currentOrg;

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen || !isEditing) {
            setStatus(initialStatus);
        }
    };

    const formProps =
        isEditing && post
            ? updatePost.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
                  post: post.id,
              })
            : storePost.form({
                  current_team: currentTeam?.slug ?? '',
                  current_org: currentOrg?.slug ?? '',
              });

    if (isDisabled) {
        return (
            <Button
                type="button"
                variant="outline"
                size="sm"
                className={className}
                disabled
            >
                {newButtonTitle}
            </Button>
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className={className}
                    >
                        {newButtonTitle}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <Form
                    key={`${isEditing ? `edit-${post?.id}` : 'create'}-${String(open)}`}
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
                                        ? 'Обновите основные поля записи. Если очистить slug, он будет создан заново.'
                                        : 'Заполните основные поля записи. Slug можно оставить пустым, тогда он будет создан автоматически.'}
                                </DialogDescription>
                            </DialogHeader>

                            <input
                                type="hidden"
                                name="type"
                                value={post?.type ?? activeType}
                            />

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="post-title">
                                        Заголовок
                                    </Label>
                                    <Input
                                        id="post-title"
                                        name="title"
                                        data-test="create-post-title"
                                        defaultValue={post?.title ?? ''}
                                        placeholder="Например, О компании"
                                        required
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="post-slug">Slug</Label>
                                    <Input
                                        id="post-slug"
                                        name="slug"
                                        data-test="create-post-slug"
                                        defaultValue={post?.slug ?? ''}
                                        placeholder="o-kompanii"
                                    />
                                    <InputError message={errors.slug} />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="post-status">
                                            Статус
                                        </Label>
                                        <Select
                                            name="status"
                                            value={status}
                                            onValueChange={setStatus}
                                        >
                                            <SelectTrigger
                                                id="post-status"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Выберите статус" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {POST_STATUS_OPTIONS.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="post-published-at">
                                            Дата публикации
                                        </Label>
                                        <Input
                                            id="post-published-at"
                                            name="published_at"
                                            type="datetime-local"
                                            defaultValue={toDateTimeLocalValue(
                                                post?.published_at,
                                            )}
                                        />
                                        <InputError
                                            message={errors.published_at}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="post-excerpt">
                                        Краткое описание
                                    </Label>
                                    <textarea
                                        id="post-excerpt"
                                        name="excerpt"
                                        className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                                        defaultValue={post?.excerpt ?? ''}
                                        placeholder="Короткий текст для списка записей"
                                    />
                                    <InputError message={errors.excerpt} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="post-content">
                                        Содержимое
                                    </Label>
                                    <textarea
                                        id="post-content"
                                        name="content"
                                        className="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                                        defaultValue={post?.content ?? ''}
                                        placeholder="Основной текст записи"
                                    />
                                    <InputError message={errors.content} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Отмена
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="create-post-submit"
                                    disabled={processing}
                                >
                                    {processing ? processingTitle : submitTitle}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function toDateTimeLocalValue(value?: string | null): string {
    return value ? value.slice(0, 16) : '';
}

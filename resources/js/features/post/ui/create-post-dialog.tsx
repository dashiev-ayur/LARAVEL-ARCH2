import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { store as storePost } from '@/routes/posts';
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
import { PostFormFields } from './post-form-fields';

type CreatePostDialogProps = {
    currentTeam: { slug: string } | null;
    currentOrg: { slug: string } | null;
    activeType: string;
    newButtonTitle?: string;
    trigger?: ReactNode;
    className?: string;
};

/**
 * Диалог создания записи активного типа.
 */
export function CreatePostDialog({
    currentTeam,
    currentOrg,
    activeType,
    newButtonTitle = 'Новая запись',
    trigger,
    className,
}: CreatePostDialogProps) {
    const [open, setOpen] = useState(false);
    const isDisabled = !currentTeam || !currentOrg;

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
        <Dialog open={open} onOpenChange={setOpen}>
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
                    key={`create-${String(open)}`}
                    {...storePost.form({
                        current_team: currentTeam.slug,
                        current_org: currentOrg.slug,
                    })}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>{newButtonTitle}</DialogTitle>
                                <DialogDescription>
                                    Заполните основные поля записи. Slug можно
                                    оставить пустым, тогда он будет создан
                                    автоматически.
                                </DialogDescription>
                            </DialogHeader>

                            <PostFormFields
                                activeType={activeType}
                                errors={errors}
                            />

                            <DialogFooter>
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
                                        data-test="post-submit"
                                        disabled={processing}
                                    >
                                        {processing ? 'Создание...' : 'Создать'}
                                    </Button>
                                </div>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

import { useState } from 'react';
import InputError from '@/components/input-error';
import type { PostListRow } from '@/entities/post';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/ui/select';

type PostFormFieldsProps = {
    activeType: string;
    post?: PostListRow;
    errors: Partial<Record<string, string>>;
};

const POST_STATUS_OPTIONS = [
    { value: 'draft', label: 'Черновик' },
    { value: 'scheduled', label: 'Запланирована' },
    { value: 'published', label: 'Опубликована' },
    { value: 'archived', label: 'В архиве' },
] as const;

/**
 * Общие поля формы записи для сценариев создания и редактирования.
 */
export function PostFormFields({
    activeType,
    post,
    errors,
}: PostFormFieldsProps) {
    const [status, setStatus] = useState(post?.status ?? 'draft');

    return (
        <>
            <input type="hidden" name="type" value={post?.type ?? activeType} />

            <div className="grid gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="post-title">Заголовок</Label>
                    <Input
                        id="post-title"
                        name="title"
                        data-test="post-title"
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
                        data-test="post-slug"
                        defaultValue={post?.slug ?? ''}
                        placeholder="o-kompanii"
                    />
                    <InputError message={errors.slug} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="post-status">Статус</Label>
                        <Select
                            name="status"
                            value={status}
                            onValueChange={setStatus}
                        >
                            <SelectTrigger id="post-status" className="w-full">
                                <SelectValue placeholder="Выберите статус" />
                            </SelectTrigger>
                            <SelectContent>
                                {POST_STATUS_OPTIONS.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
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
                        <InputError message={errors.published_at} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="post-excerpt">Краткое описание</Label>
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
                    <Label htmlFor="post-content">Содержимое</Label>
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
        </>
    );
}

function toDateTimeLocalValue(value?: string | null): string {
    return value ? value.slice(0, 16) : '';
}

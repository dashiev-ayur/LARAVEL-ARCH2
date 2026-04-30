import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import type { PageListRow, PageStatus } from '@/entities/page';
import { Checkbox } from '@/shared/ui/checkbox';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/ui/select';

type PageFormFieldsProps = {
    pages: PageListRow[];
    page?: PageListRow;
    pageStatuses: readonly PageStatus[];
    errors: Partial<Record<string, string>>;
};

const ROOT_PARENT_VALUE = 'root';

const statusLabels: Record<PageStatus, string> = {
    draft: 'Черновик',
    review: 'На проверке',
    published: 'Опубликована',
};

function getAvailableParentPages(
    pages: PageListRow[],
    page?: PageListRow,
): PageListRow[] {
    if (!page) {
        return pages;
    }

    const childrenByParentId = new Map<number, PageListRow[]>();

    for (const parentPage of pages) {
        if (parentPage.parent_id === null) {
            continue;
        }

        childrenByParentId.set(parentPage.parent_id, [
            ...(childrenByParentId.get(parentPage.parent_id) ?? []),
            parentPage,
        ]);
    }

    const excludedIds = new Set<number>([page.id]);
    const stack = [...(childrenByParentId.get(page.id) ?? [])];

    while (stack.length > 0) {
        const childPage = stack.pop();

        if (!childPage || excludedIds.has(childPage.id)) {
            continue;
        }

        excludedIds.add(childPage.id);
        stack.push(...(childrenByParentId.get(childPage.id) ?? []));
    }

    return pages.filter((parentPage) => !excludedIds.has(parentPage.id));
}

export function PageFormFields({
    pages,
    page,
    pageStatuses,
    errors,
}: PageFormFieldsProps) {
    const [parentId, setParentId] = useState(
        page?.parent_id ? String(page.parent_id) : ROOT_PARENT_VALUE,
    );
    const [status, setStatus] = useState<PageStatus>(page?.status ?? 'draft');
    const [noindex, setNoindex] = useState(page?.noindex ?? false);
    const parentOptions = useMemo(
        () => getAvailableParentPages(pages, page),
        [pages, page],
    );

    return (
        <>
            <input
                type="hidden"
                name="parent_id"
                value={parentId === ROOT_PARENT_VALUE ? '' : parentId}
            />
            <input type="hidden" name="status" value={status} />
            <input type="hidden" name="noindex" value={noindex ? '1' : '0'} />

            <div className="grid gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="page-title">Название</Label>
                    <Input
                        id="page-title"
                        name="title"
                        data-test="page-title"
                        defaultValue={page?.title ?? ''}
                        placeholder="Например, О компании"
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="page-slug">Slug</Label>
                    <Input
                        id="page-slug"
                        name="slug"
                        data-test="page-slug"
                        defaultValue={page?.slug ?? ''}
                        placeholder="about"
                    />
                    <InputError message={errors.slug} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="page-parent">Родительская страница</Label>
                    <Select value={parentId} onValueChange={setParentId}>
                        <SelectTrigger id="page-parent" className="w-full">
                            <SelectValue placeholder="Выберите родительскую страницу" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ROOT_PARENT_VALUE}>
                                Корневой уровень
                            </SelectItem>
                            {parentOptions.map((parentPage) => (
                                <SelectItem
                                    key={parentPage.id}
                                    value={String(parentPage.id)}
                                >
                                    {'-- '.repeat(parentPage.depth)}
                                    {parentPage.title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.parent_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="page-status">Статус</Label>
                    <Select
                        value={status}
                        onValueChange={(value) => setStatus(value as PageStatus)}
                    >
                        <SelectTrigger id="page-status" className="w-full">
                            <SelectValue placeholder="Выберите статус" />
                        </SelectTrigger>
                        <SelectContent>
                            {pageStatuses.map((pageStatus) => (
                                <SelectItem key={pageStatus} value={pageStatus}>
                                    {statusLabels[pageStatus] ?? pageStatus}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="page-seo-title">SEO title</Label>
                    <Input
                        id="page-seo-title"
                        name="seo_title"
                        defaultValue={page?.seo_title ?? ''}
                        placeholder="Заголовок для поисковых систем"
                    />
                    <InputError message={errors.seo_title} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="page-meta-description">
                        Meta description
                    </Label>
                    <textarea
                        id="page-meta-description"
                        name="meta_description"
                        className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        defaultValue={page?.meta_description ?? ''}
                        placeholder="Краткое описание страницы"
                    />
                    <InputError message={errors.meta_description} />
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={noindex}
                        onCheckedChange={(checked) => setNoindex(checked === true)}
                    />
                    Запретить индексацию страницы
                </label>
                <InputError message={errors.noindex} />
            </div>
        </>
    );
}

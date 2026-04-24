type PostTitleSlugCellProps = {
    title: string;
    slug: string;
};

/**
 * Две строки: заголовок + путь /slug.
 */
export function PostTitleSlugCell({ title, slug }: PostTitleSlugCellProps) {
    return (
        <div className="flex flex-col gap-1">
            <span className="font-medium text-foreground">{title}</span>
            <span className="text-xs text-muted-foreground">/{slug}</span>
        </div>
    );
}

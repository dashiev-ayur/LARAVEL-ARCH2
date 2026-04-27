type PostTitleExcerptCellProps = {
    title: string;
    excerpt: string | null;
};

/**
 * Две строки: заголовок + краткое описание.
 */
export function PostTitleExcerptCell({
    title,
    excerpt,
}: PostTitleExcerptCellProps) {
    return (
        <div className="flex flex-col gap-1">
            <span className="font-medium text-foreground">{title}</span>
            {excerpt ? (
                <span className="text-xs text-muted-foreground">{excerpt}</span>
            ) : null}
        </div>
    );
}

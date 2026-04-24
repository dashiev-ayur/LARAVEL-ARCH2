type PostStatusCellProps = {
    status: string;
};

/**
 * Статус записи в таблице.
 */
export function PostStatusCell({ status }: PostStatusCellProps) {
    return <span className="inline-flex rounded-md border px-2 py-0.5 text-xs">{status}</span>;
}

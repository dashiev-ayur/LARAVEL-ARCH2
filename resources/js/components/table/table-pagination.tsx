import type { Table } from "@tanstack/react-table";

type TablePaginationProps<TData> = {
  table: Table<TData>;
  /** Для серверной пагинации: всего записей с бэкенда (иначе берётся длина текущей страницы) */
  totalRowCount?: number;
};

function getVisiblePageNumbers(
  currentPage: number,
  totalPages: number,
  maxButtons: number,
): number[] {
  if (totalPages <= 0) {
    return [];
  }
  if (totalPages <= maxButtons) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }
  const half = Math.floor(maxButtons / 2);
  const start = Math.max(1, currentPage - half);
  const end = start + maxButtons - 1;
  if (end > totalPages) {
    const newEnd = totalPages;
    const newStart = Math.max(1, newEnd - maxButtons + 1);
    return Array.from(
      { length: newEnd - newStart + 1 },
      (_, i) => newStart + i,
    );
  }
  return Array.from({ length: maxButtons }, (_, i) => start + i);
}

export function TablePagination<TData>({
  table,
  totalRowCount,
}: TablePaginationProps<TData>) {
  const totalRows =
    totalRowCount ?? table.getPrePaginationRowModel().rows.length;
  const { pageIndex, pageSize } = table.getState().pagination;
  const pageCount = table.getPageCount();
  const from = totalRows === 0 ? 0 : pageIndex * pageSize + 1;
  const to = Math.min((pageIndex + 1) * pageSize, totalRows);
  const currentPage = pageIndex + 1;
  const visiblePages = getVisiblePageNumbers(currentPage, pageCount, 5);

  return (
    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 px-1">
      <p className="text-xs text-gray-400">
        Показано{" "}
        <span className="font-medium text-gray-600">
          {from}-{to}
        </span>{" "}
        из{" "}
        <span className="font-medium text-gray-600">{totalRows}</span>
      </p>
      <div className="flex items-center gap-1.5">
        <button
          type="button"
          aria-label="Предыдущая страница"
          disabled={!table.getCanPreviousPage()}
          onClick={() => table.previousPage()}
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors enabled:hover:bg-gray-100 enabled:hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <span className="text-lg leading-none" aria-hidden>
            ‹
          </span>
        </button>
        <div className="flex items-center gap-1.5">
          {visiblePages.map((page) => {
            const isActive = page === currentPage;
            return (
              <button
                key={page}
                type="button"
                aria-label={`Страница ${page}`}
                aria-current={isActive ? "page" : undefined}
                onClick={() => table.setPageIndex(page - 1)}
                className={`inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm font-medium transition-colors ${
                  isActive
                    ? "bg-violet-300 text-white"
                    : "border border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50"
                }`}
              >
                {page}
              </button>
            );
          })}
        </div>
        <button
          type="button"
          aria-label="Следующая страница"
          disabled={!table.getCanNextPage()}
          onClick={() => table.nextPage()}
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors enabled:hover:bg-gray-100 enabled:hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <span className="text-lg leading-none" aria-hidden>
            ›
          </span>
        </button>
      </div>
    </div>
  );
}

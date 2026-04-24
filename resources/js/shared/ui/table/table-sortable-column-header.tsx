import type { Column } from "@tanstack/react-table";
import { ChevronDownIcon, ChevronUpIcon } from "lucide-react";
import type { ReactNode } from "react";

type TableSortableColumnHeaderProps<TData> = {
  column: Column<TData, unknown>;
  children: ReactNode;
};

export function TableSortableColumnHeader<TData>({
  column,
  children,
}: TableSortableColumnHeaderProps<TData>) {
  const sorted = column.getIsSorted();
  return (
    <button
      type="button"
      onClick={column.getToggleSortingHandler()}
      className="-mx-1 inline-flex max-w-full items-center gap-1 rounded px-1 text-left text-inherit transition-colors hover:bg-gray-50 hover:text-gray-700"
    >
      <span className="min-w-0">{children}</span>
      {sorted === "asc" && (
        <ChevronUpIcon className="size-3.5 shrink-0 text-gray-500" />
      )}
      {sorted === "desc" && (
        <ChevronDownIcon className="size-3.5 shrink-0 text-gray-500" />
      )}
    </button>
  );
}

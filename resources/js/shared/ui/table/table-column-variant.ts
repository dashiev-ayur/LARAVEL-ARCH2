export type TableCellVariant = "default" | "select" | "actions";

/** Сопоставление id колонки TanStack Table с вариантом ячейки (чекбокс, действия). */
export function tableColumnVariant(columnId: string): TableCellVariant {
  if (columnId === "select") {
    return "select";
  }
  if (columnId === "actions") {
    return "actions";
  }
  return "default";
}

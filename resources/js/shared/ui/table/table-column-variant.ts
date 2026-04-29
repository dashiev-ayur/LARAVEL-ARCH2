export type TableCellVariant = "default" | "select" | "drag" | "actions";

/** Сопоставление id колонки TanStack Table с вариантом ячейки (чекбокс, действия). */
export function tableColumnVariant(columnId: string): TableCellVariant {
  if (columnId === "select") {
    return "select";
  }

  if (columnId === "drag") {
    return "drag";
  }

  if (columnId === "actions") {
    return "actions";
  }

  return "default";
}

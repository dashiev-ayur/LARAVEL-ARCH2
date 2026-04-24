/* Составной Table: публичный API через Object.assign; подкомпоненты — приватные. */
import type { HTMLAttributes, TdHTMLAttributes, ThHTMLAttributes } from "react";

import type { TableCellVariant } from "./table-column-variant";

const headVariantClass: Record<TableCellVariant, string> = {
  default: "px-4 py-3 text-xs font-medium text-slate-400",
  select: "w-12 px-4 py-3 text-center text-xs font-medium text-slate-400",
  actions: "w-28 px-4 py-3 text-right text-xs font-medium text-slate-400",
};

const cellVariantClass: Record<TableCellVariant, string> = {
  default: "px-4 py-3 align-middle text-sm",
  select: "w-12 px-4 py-3 text-center align-middle text-sm",
  actions: "px-4 py-3 text-right align-middle text-sm",
};

type RootProps = HTMLAttributes<HTMLTableElement>;

function Root({ className = "", ...rest }: RootProps) {
  return (
    <table
      className={`min-w-full border-collapse text-left ${className}`.trim()}
      {...rest}
    />
  );
}

function ScrollArea({
  className = "",
  ...rest
}: HTMLAttributes<HTMLDivElement>) {
  return <div className={`overflow-x-auto ${className}`.trim()} {...rest} />;
}

function Card({ className = "", ...rest }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={`overflow-hidden rounded-lg border border-gray-100 bg-white ${className}`.trim()}
      {...rest}
    />
  );
}

function Toolbar({
  className = "",
  ...rest
}: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={`flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-4 ${className}`.trim()}
      {...rest}
    />
  );
}

function Header({
  className = "",
  ...rest
}: HTMLAttributes<HTMLTableSectionElement>) {
  return <thead className={className} {...rest} />;
}

function Body({
  className = "",
  ...rest
}: HTMLAttributes<HTMLTableSectionElement>) {
  return <tbody className={className} {...rest} />;
}

function Footer({
  className = "",
  ...rest
}: HTMLAttributes<HTMLTableSectionElement>) {
  return <tfoot className={className} {...rest} />;
}

type RowProps = HTMLAttributes<HTMLTableRowElement> & {
  /** Строка в thead */
  header?: boolean;
  /** Выбранная строка (например, row selection) */
  selected?: boolean;
};

function Row({
  className = "",
  header = false,
  selected = false,
  ...rest
}: RowProps) {
  const base = header
    ? "border-b border-gray-100 bg-white"
    : `border-b border-gray-100 transition-colors hover:bg-gray-50 ${
        selected ? "border-l-4 border-l-blue-600 bg-blue-50/30" : ""
      }`;

  return <tr className={`${base} ${className}`.trim()} {...rest} />;
}

type HeadProps = ThHTMLAttributes<HTMLTableCellElement> & {
  variant?: TableCellVariant;
};

function Head({
  variant = "default",
  className = "",
  ...rest
}: HeadProps) {
  return (
    <th
      className={`${headVariantClass[variant]} ${className}`.trim()}
      {...rest}
    />
  );
}

type CellProps = TdHTMLAttributes<HTMLTableCellElement> & {
  variant?: TableCellVariant;
};

function Cell({
  variant = "default",
  className = "",
  ...rest
}: CellProps) {
  return (
    <td
      className={`${cellVariantClass[variant]} ${className}`.trim()}
      {...rest}
    />
  );
}

export const Table = Object.assign(Root, {
  Card,
  ScrollArea,
  Toolbar,
  Header,
  Body,
  Footer,
  Row,
  Head,
  Cell,
});

Root.displayName = "Table";
ScrollArea.displayName = "Table.ScrollArea";
Card.displayName = "Table.Card";
Toolbar.displayName = "Table.Toolbar";
Header.displayName = "Table.Header";
Body.displayName = "Table.Body";
Footer.displayName = "Table.Footer";
Row.displayName = "Table.Row";
Head.displayName = "Table.Head";
Cell.displayName = "Table.Cell";

type DateTimeValue = string | number | Date | null | undefined;

const pad = (value: number): string => value.toString().padStart(2, '0');

const toDate = (value: DateTimeValue): Date | null => {
    if (!value) {
        return null;
    }

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
};

const formatDatePart = (date: Date): string =>
    [
        date.getFullYear().toString().padStart(4, '0'),
        pad(date.getMonth() + 1),
        pad(date.getDate()),
    ].join('-');

/**
 * Форматирует дату для вывода в UI: YYYY-MM-DD.
 */
export function formatDate(value: DateTimeValue, fallback = '—'): string {
    const date = toDate(value);

    return date ? formatDatePart(date) : fallback;
}

/**
 * Форматирует дату для вывода в UI: YYYY-MM-DD HH:mm.
 */
export function formatDateTime(value: DateTimeValue, fallback = '—'): string {
    const date = toDate(value);

    if (!date) {
        return fallback;
    }

    return [
        formatDatePart(date),
        [pad(date.getHours()), pad(date.getMinutes())].join(':'),
    ].join(' ');
}

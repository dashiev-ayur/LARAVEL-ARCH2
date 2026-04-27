import { useEffect, useState } from 'react';

/**
 * Возвращает значение с задержкой обновления.
 */
export function useDebounce<TValue>(value: TValue, delay: number): TValue {
    const [debouncedValue, setDebouncedValue] = useState<TValue>(value);

    useEffect(() => {
        const timeoutId = window.setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            window.clearTimeout(timeoutId);
        };
    }, [delay, value]);

    return debouncedValue;
}

import * as React from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ButtonNewPostProps = Omit<React.ComponentProps<typeof Button>, 'onClick' | 'children'> & {
    /** С сервера (`PostTypeHandler::getNewButtonTitle`), без дублирования в JS. */
    newButtonTitle: string;
    onClick?: React.MouseEventHandler<HTMLButtonElement>;
};

/**
 * Кнопка создания записи. Без `onClick` — заглушка `alert` с той же подписью, что и на кнопке.
 */
export function ButtonNewPost({ newButtonTitle, onClick, className, ...rest }: ButtonNewPostProps) {
    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className={cn(className)}
            onClick={onClick ?? (() => window.alert(newButtonTitle))}
            {...rest}
        >
            {newButtonTitle}
        </Button>
    );
}

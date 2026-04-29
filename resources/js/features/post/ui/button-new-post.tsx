import { Link } from '@inertiajs/react';
import * as React from 'react';

import { cn } from '@/shared/lib/utils';
import { Button } from '@/shared/ui/button';

type ButtonNewPostProps = Omit<
    React.ComponentProps<typeof Button>,
    'onClick' | 'children'
> & {
    /** С сервера (`PostTypeHandler::getNewButtonTitle`), без дублирования в JS. */
    newButtonTitle: string;
    /** Когда появится маршрут создания — передаётся URL из Wayfinder; иначе кнопка-заглушка. */
    href?: string;
    onClick?: React.MouseEventHandler<HTMLButtonElement>;
};

/**
 * Действие «новая запись»: при `href` — Inertia-ссылка; иначе — заглушка до появления бэка.
 */
export function ButtonNewPost({
    newButtonTitle,
    href,
    onClick,
    className,
    ...rest
}: ButtonNewPostProps) {
    if (href) {
        return (
            <Button
                variant="outline"
                size="sm"
                className={cn(className)}
                asChild
                {...rest}
            >
                <Link href={href}>{newButtonTitle}</Link>
            </Button>
        );
    }

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

/**
 * Слой shared — UI-кит, lib, нейтральные хуки (публичный API через реэкспорты).
 * Импорты в приложении предпочтительны как `@/shared/...` / `@/shared/ui/...` — без обязательного barrel.
 */
export { initializeTheme, useAppearance } from './hooks/use-appearance';
export { useClipboard } from './hooks/use-clipboard';
export { useCurrentUrl } from './hooks/use-current-url';
export { useFlashToast } from './hooks/use-flash-toast';
export { useInitials } from './hooks/use-initials';
export { useIsMobile } from './hooks/use-mobile';
export { useMobileNavigation } from './hooks/use-mobile-navigation';
export { formatDate, formatDateTime } from './lib/format-date-time';
export { cn, toUrl } from './lib/utils';

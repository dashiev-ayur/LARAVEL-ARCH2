import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import type { AppLayoutProps } from '@/types';
import { AppHeader } from '@/widgets';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header">{children}</AppContent>
        </AppShell>
    );
}

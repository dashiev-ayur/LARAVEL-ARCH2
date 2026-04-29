import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FileText,
    FolderGit2,
    FolderTree,
    Info,
    LayoutGrid,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { OrgSwitcher } from '@/components/org-switcher';
import { TeamSwitcher } from '@/components/team-switcher';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import { index as postsIndex } from '@/routes/posts';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/shared/ui/sidebar';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const currentOrg = page.props.currentOrg;
    const currentTeam = page.props.currentTeam;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';
    const postsUrl =
        currentTeam && currentOrg
            ? postsIndex.url({
                  current_team: currentTeam.slug,
                  current_org: currentOrg.slug,
              })
            : dashboardUrl;
    const categoriesUrl =
        currentTeam && currentOrg
            ? categoriesIndex.url({
                  current_team: currentTeam.slug,
                  current_org: currentOrg.slug,
              })
            : dashboardUrl;

    const mainNavItems: NavItem[] = [
        {
            title: 'О проекте',
            href: '/about',
            icon: Info,
        },
        {
            title: 'Главная',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Записи',
            href: postsUrl,
            icon: FileText,
            disabled: !currentOrg,
        },
        {
            title: 'Категории',
            href: categoriesUrl,
            icon: FolderTree,
            disabled: !currentOrg,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <OrgSwitcher
                            key={page.props.currentTeam?.id ?? 'no-team'}
                        />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

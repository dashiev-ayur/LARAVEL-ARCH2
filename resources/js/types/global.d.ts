import type { Auth } from '@/types/auth';
import type { Org } from '@/types/orgs';
import type { Team } from '@/types/teams';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            currentOrg: Org | null;
            orgs: Org[];
            [key: string]: unknown;
        };
    }
}

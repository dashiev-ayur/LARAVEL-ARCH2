import { usePage } from "@inertiajs/react";
import type { Org } from "@/types/orgs";
import type { Team } from "@/types/teams";

type StatsHeaderProps = {
    userName: string;
};

export function StatsHeader({ userName }: StatsHeaderProps) {
    const currentTeam: Team | null = usePage().props.currentTeam;
    const currentTeamOrgs: Org[] = currentTeam?.orgs ?? [];

    const teamName = currentTeam ? currentTeam.name : 'Без команды';
    
    
    return (
        <div className="p-4">
            <h2 className="text-lg font-medium">Статистика</h2>
            <p className="mt-1 text-sm text-muted-foreground">Привет, {userName}!</p>
            <p className="mt-1 text-sm text-muted-foreground">Команда: {teamName}</p>
            <p className="mt-1 text-sm text-muted-foreground">
                Организации:
                <ul className="list-disc list-inside">
                    {currentTeamOrgs.map((org: Org) => (
                        <li key={org.id}>{org.name}</li>
                    ))}
                </ul>
            </p>
        </div>
    );
}

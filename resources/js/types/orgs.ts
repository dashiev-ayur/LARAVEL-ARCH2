import type { Team } from "./teams";

export type Org = {
    id: number;
    team_id: number;

    name: string;
    slug: string;
    about: string;
    logo: string;
    website: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    status: string;

    team: Team;

    created_at: string;
    updated_at: string;
};
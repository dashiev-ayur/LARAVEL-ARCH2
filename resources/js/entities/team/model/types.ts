import type { Team, TeamMember } from '@/types/teams';

/** Сущность team на фронте; совпадает с DTO Inertia. */
export type TeamEntity = Team;

/** Участник team в списках/карточках. */
export type TeamMemberEntity = TeamMember;

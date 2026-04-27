/**
 * Слой entities — сущности без полноценного пользовательского сценария.
 * Публичный API: импорты из @/entities/<name> (user, team, org, post).
 */
export type { Org, OrgEntity } from './org';
export type { PostListRow } from './post';
export { PostStatusCell, PostTitleExcerptCell } from './post';
export type {
    RoleOption,
    Team,
    TeamEntity,
    TeamInvitation,
    TeamMember,
    TeamMemberEntity,
    TeamPermissions,
    TeamRole,
} from './team';
export { UserInfo, type UserEntity, type UserInfoTeam } from './user';

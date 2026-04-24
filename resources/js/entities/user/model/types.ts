import type { User } from '@/types';
import type { Team } from '@/types/teams';

/**
 * Контракт пользователя для сущностного UI (согласован с DTO Inertia: User).
 */
export type UserEntity = Pick<User, 'name' | 'email' | 'avatar'>;

/**
 * Подзаголовок team в UserInfo; типы только из @/types (без импорта других слайсов entities).
 */
export type UserInfoTeam = Pick<Team, 'name'>;

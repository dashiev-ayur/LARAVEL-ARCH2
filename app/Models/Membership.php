<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['team_id', 'user_id', 'role'])]
class Membership extends Pivot
{
    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'team_members';

    /**
     * Указывает, автоинкрементируются ли идентификаторы.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Команда, к которой относится членство.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Пользователь, связанный с этим членством.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Атрибуты с приведением типов (casts).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}

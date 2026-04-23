<?php

namespace App\Models;

use App\Enums\OrgStatus;
use Database\Factories\OrgFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'name',
    'slug',
    'about',
    'logo',
    'website',
    'email',
    'phone',
    'address',
    'city',
    'status',
])]
#[Table('orgs')]
class Org extends Model
{
    /** @use HasFactory<OrgFactory> */
    use HasFactory;

    /**
     * Атрибуты с приведением типов (casts).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrgStatus::class,
        ];
    }

    /**
     * Родительская команда
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Записи организации.
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'org_id');
    }

    /**
     * Категории организации.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'org_id');
    }
}

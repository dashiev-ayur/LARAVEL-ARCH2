<?php

namespace App\Models;

use App\Enums\PageStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'org_id',
    'author_id',
    'parent_id',
    'reviewer_id',
    'status',
    'acl_resource',
    'sort_order',
    'slug',
    'path',
    'depth',
    'title',
    'excerpt',
    'content',
    'template',
    'seo_title',
    'meta_description',
    'noindex',
    'content_hash',
    'generated_hash',
    'generated_at',
    'needs_generation',
    'published_at',
    'reviewed_at',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты с приведением типов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'sort_order' => 'integer',
            'depth' => 'integer',
            'noindex' => 'boolean',
            'needs_generation' => 'boolean',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Организация, которой принадлежит страница.
     *
     * @return BelongsTo<Org, $this>
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Пользователь-автор страницы.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Пользователь, проверивший страницу.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Родительская страница в дереве.
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Дочерние страницы в дереве.
     *
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}

<?php

namespace App\Models;

use App\Enums\OrgStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
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
class Org extends Model
{
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
}

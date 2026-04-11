<?php

namespace App\Models;

use App\Enums\OrgStatus;
use Database\Factories\OrgFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}

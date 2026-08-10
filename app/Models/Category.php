<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'olx_id',
        'olx_external_id',
        'parent_id',
        'name',
        'slug',
        'level',
        'display_priority',
        'purpose',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'display_priority' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CategoryField::class, 'category_id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class, 'category_id');
    }
}

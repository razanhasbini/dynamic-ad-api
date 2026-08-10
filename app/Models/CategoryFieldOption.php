<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryFieldOption extends Model
{
    protected $fillable = [
        'olx_id',
        'category_field_id',
        'parent_option_id',
        'value',
        'label',
        'slug',
        'display_priority',
    ];

    protected function casts(): array
    {
        return [
            'display_priority' => 'integer',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }

    public function parentOption(): BelongsTo
    {
        return $this->belongsTo(CategoryFieldOption::class, 'parent_option_id');
    }

    public function childOptions(): HasMany
    {
        return $this->hasMany(CategoryFieldOption::class, 'parent_option_id');
    }
}

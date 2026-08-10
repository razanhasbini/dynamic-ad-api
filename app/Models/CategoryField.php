<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryField extends Model
{
    protected $fillable = [
        'olx_id',
        'category_id',
        'parent_field_id',
        'attribute',
        'name',
        'value_type',
        'filter_type',
        'is_mandatory',
        'state',
        'roles',
        'min_value',
        'max_value',
        'min_length',
        'max_length',
        'display_priority',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'roles' => 'array',
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
            'min_length' => 'integer',
            'max_length' => 'integer',
            'display_priority' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function parentField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'parent_field_id');
    }

    public function childFields(): HasMany
    {
        return $this->hasMany(CategoryField::class, 'parent_field_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CategoryFieldOption::class, 'category_field_id');
    }

    public function adFieldValues(): HasMany
    {
        return $this->hasMany(AdFieldValue::class, 'category_field_id');
    }
}

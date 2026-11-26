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

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return BelongsTo<CategoryField, $this> */
    public function parentField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'parent_field_id');
    }

    /** @return HasMany<CategoryField, $this> */
    public function childFields(): HasMany
    {
        return $this->hasMany(CategoryField::class, 'parent_field_id');
    }

    /** @return HasMany<CategoryFieldOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(CategoryFieldOption::class, 'category_field_id');
    }

    /** @return HasMany<AdFieldValue, $this> */
    public function adFieldValues(): HasMany
    {
        return $this->hasMany(AdFieldValue::class, 'category_field_id');
    }
}

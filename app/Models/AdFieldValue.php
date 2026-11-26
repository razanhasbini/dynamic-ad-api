<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdFieldValue extends Model
{
    protected $fillable = [
        'ad_id',
        'category_field_id',
        'category_field_option_id',
        'value',
    ];

    /** @return BelongsTo<Ad, $this> */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    /** @return BelongsTo<CategoryField, $this> */
    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }

    /** @return BelongsTo<CategoryFieldOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(
            CategoryFieldOption::class,
            'category_field_option_id'
        );
    }
}

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

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(
            CategoryFieldOption::class,
            'category_field_option_id'
        );
    }
}

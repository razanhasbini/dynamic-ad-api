<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $groupedFields = $this->fieldValues
            ->groupBy('category_field_id')
            ->map(function ($fieldValues) {
                $firstFieldValue = $fieldValues->first();
                $field = $firstFieldValue->categoryField;

                if ($field->value_type === 'enum_multiple') {
                    return [
                        'attribute' => $field->attribute,
                        'name' => $field->name,
                        'value' => $fieldValues
                            ->map(fn ($fieldValue) => $fieldValue->option?->label)
                            ->filter()
                            ->values(),
                    ];
                }

                return [
                    'attribute' => $field->attribute,
                    'name' => $field->name,
                    'value' => $firstFieldValue->option
                        ? $firstFieldValue->option->label
                        : $firstFieldValue->value,
                ];
            })
            ->values();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,

            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],

            'fields' => $groupedFields,

            'created_at' => $this->created_at,
        ];
    }
}

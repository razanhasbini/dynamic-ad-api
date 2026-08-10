<?php

namespace App\Http\Requests;

use App\Models\CategoryField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'fields' => [
                'nullable',
                'array',
            ],
        ];

        $categoryId = $this->input('category_id');

        if (! $categoryId) {
            return $rules;
        }

        $categoryFields = CategoryField::where('category_id', $categoryId)
            ->where('state', 'active')
            ->with([
                'options',
                'parentField',
            ])
            ->get();

        foreach ($categoryFields as $field) {
            $fieldKey = "fields.{$field->attribute}";

            $fieldRules = [];

            if ($field->is_mandatory) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field->value_type) {
                case 'integer':
                    $fieldRules[] = 'integer';

                    if ($field->min_value !== null) {
                        $fieldRules[] = 'min:'.$field->min_value;
                    }

                    if ($field->max_value !== null) {
                        $fieldRules[] = 'max:'.$field->max_value;
                    }

                    break;

                case 'float':
                    $fieldRules[] = 'numeric';

                    if ($field->min_value !== null) {
                        $fieldRules[] = 'min:'.$field->min_value;
                    }

                    if ($field->max_value !== null) {
                        $fieldRules[] = 'max:'.$field->max_value;
                    }

                    break;

                case 'string':
                    $fieldRules[] = 'string';

                    if ($field->min_length !== null) {
                        $fieldRules[] = 'min:'.$field->min_length;
                    }

                    if ($field->max_length !== null) {
                        $fieldRules[] = 'max:'.$field->max_length;
                    }

                    break;

                case 'enum':
                    $optionRule = Rule::exists(
                        'category_field_options',
                        'id'
                    )->where(
                        'category_field_id',
                        $field->id
                    );

                    if ($field->parentField) {
                        $parentValue = $this->input(
                            "fields.{$field->parentField->attribute}"
                        );

                        if ($parentValue !== null) {
                            $optionRule->where(
                                'parent_option_id',
                                $parentValue
                            );
                        }
                    }

                    $fieldRules[] = $optionRule;

                    break;

                case 'enum_multiple':
                    $fieldRules[] = 'array';

                    if ($field->is_mandatory) {
                        $fieldRules[] = 'min:1';
                    }

                    $optionRule = Rule::exists(
                        'category_field_options',
                        'id'
                    )->where(
                        'category_field_id',
                        $field->id
                    );

                    if ($field->parentField) {
                        $parentValue = $this->input(
                            "fields.{$field->parentField->attribute}"
                        );

                        if ($parentValue !== null) {
                            $optionRule->where(
                                'parent_option_id',
                                $parentValue
                            );
                        }
                    }

                    $rules["{$fieldKey}.*"] = [
                        'integer',
                        'distinct',
                        $optionRule,
                    ];

                    break;
            }

            $rules[$fieldKey] = $fieldRules;
        }

        return $rules;
    }
}

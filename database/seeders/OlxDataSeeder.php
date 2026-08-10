<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOption;
use App\Services\OlxApiService;
use Illuminate\Database\Seeder;

class OlxDataSeeder extends Seeder
{
    private function flattenCategories(array $categories): array // I flattened the category tree first so every category can be processed the same way, regardless of how deeply it is nested in the OLX response.
    {
        $flattened = [];

        foreach ($categories as $category) {
            $flattened[] = $category;

            if (! empty($category['children'])) {
                $flattened = array_merge(
                    $flattened,
                    $this->flattenCategories($category['children'])
                );
            }
        }

        return $flattened;
    }

    private function getAllFields(array $categoryFieldData): array
    {
        $fields = [];

        $allFields = array_merge(
            $categoryFieldData['flatFields'] ?? [],
            $categoryFieldData['childrenFields'] ?? []
        );

        foreach ($allFields as $field) {
            if (isset($field['id'])) {
                $fields[$field['id']] = $field;
            }
        }

        return array_values($fields);
    }

    private function getChoices(array $fieldData): array // I normalized the choices here because the OLX api does not always return them in the same shape; some fields contain flat choices while others are nested.
    {
        $choices = $fieldData['choices'] ?? [];

        if (empty($choices)) {
            return [];
        }

        $normalized = [];

        foreach ($choices as $choice) {
            if (isset($choice['id'])) {
                $normalized[] = $choice;

                continue;
            }

            if (is_array($choice)) {
                foreach ($choice as $nestedChoice) {
                    if (is_array($nestedChoice) && isset($nestedChoice['id'])) {
                        $normalized[] = $nestedChoice;
                    }
                }
            }
        }

        return $normalized;
    }

    private function findFieldByAttribute(array $fields, string $attribute): ?array
    {
        foreach ($fields as $field) {
            if (($field['attribute'] ?? null) === $attribute) {
                return $field;
            }
        }

        return null;
    }

    private function saveFields(array $categoryFieldData): void
    {
        $fields = $this->getAllFields($categoryFieldData);

        foreach ($fields as $fieldData) {
            $fieldCategory = null;

            if (! empty($fieldData['categoryID'])) {
                $fieldCategory = Category::where(
                    'olx_id',
                    $fieldData['categoryID']
                )->first();
            }

            $field = CategoryField::updateOrCreate(
                [
                    'olx_id' => $fieldData['id'],
                ],
                [
                    'category_id' => $fieldCategory?->id,
                    'parent_field_id' => null,
                    'attribute' => $fieldData['attribute'],
                    'name' => $fieldData['name'],
                    'value_type' => $fieldData['valueType'],
                    'filter_type' => $fieldData['filterType'] ?? null,
                    'is_mandatory' => $fieldData['isMandatory'] ?? false,
                    'state' => $fieldData['state'] ?? null,
                    'roles' => $fieldData['roles'] ?? null,
                    'min_value' => $fieldData['minValue'] ?? null,
                    'max_value' => $fieldData['maxValue'] ?? null,
                    'min_length' => $fieldData['minLength'] ?? null,
                    'max_length' => $fieldData['maxLength'] ?? null,
                    'display_priority' => $fieldData['displayPriority'] ?? null,
                ]
            );

            foreach ($this->getChoices($fieldData) as $choiceData) {
                if (
                    ! isset($choiceData['id']) ||
                    ! array_key_exists('value', $choiceData) ||
                    ! isset($choiceData['label'])
                ) {
                    continue;
                }

                CategoryFieldOption::updateOrCreate(
                    [
                        'olx_id' => $choiceData['id'],
                    ],
                    [
                        'category_field_id' => $field->id,
                        'parent_option_id' => null,
                        'value' => (string) $choiceData['value'],
                        'label' => $choiceData['label'],
                        'slug' => ! empty($choiceData['slug'])
                            ? $choiceData['slug']
                            : null,
                        'display_priority' => $choiceData['displayPriority'] ?? null,
                    ]
                );
            }
        }
    }

    private function resolveFieldRelationships(array $categoryFieldData): void
    {
        $fields = $this->getAllFields($categoryFieldData);

        foreach (
            $categoryFieldData['parentFieldLookup'] ?? [] as $childAttribute => $parentAttribute
        ) {
            $childFieldData = $this->findFieldByAttribute(
                $fields,
                $childAttribute
            );

            $parentFieldData = $this->findFieldByAttribute(
                $fields,
                $parentAttribute
            );

            if (! $childFieldData || ! $parentFieldData) {
                continue;
            }

            $childField = CategoryField::where(
                'olx_id',
                $childFieldData['id']
            )->first();

            $parentField = CategoryField::where(
                'olx_id',
                $parentFieldData['id']
            )->first();

            if ($childField && $parentField) {
                $childField->update([
                    'parent_field_id' => $parentField->id,
                ]);
            }
        }

        foreach ($fields as $fieldData) {
            foreach ($this->getChoices($fieldData) as $choiceData) {
                if (
                    ! isset($choiceData['id']) ||
                    empty($choiceData['parentID'])
                ) {
                    continue;
                }

                $option = CategoryFieldOption::where(
                    'olx_id',
                    $choiceData['id']
                )->first();

                $parentOption = CategoryFieldOption::where(
                    'olx_id',
                    $choiceData['parentID']
                )->first();

                if ($option && $parentOption) {
                    $option->update([
                        'parent_option_id' => $parentOption->id,
                    ]);
                }
            }
        }
    }

    public function run(OlxApiService $olxApiService): void
    {
        $categories = $olxApiService->getCategories();

        $flattenedCategories = $this->flattenCategories($categories);

        foreach ($flattenedCategories as $categoryData) {
            Category::updateOrCreate(
                [
                    'olx_id' => $categoryData['id'],
                ],
                [
                    'olx_external_id' => $categoryData['external_id'],
                    'parent_id' => null,
                    'name' => $categoryData['name'],
                    'slug' => $categoryData['slug'] ?? null,
                    'level' => $categoryData['level'] ?? null,
                    'display_priority' => $categoryData['displayPriority'] ?? null,
                    'purpose' => $categoryData['purpose'] ?? null,
                ]
            );
        }

        foreach ($flattenedCategories as $categoryData) { // Once all categories exist locally, I can safely connect each category to its parent using the local database IDs.
            if (empty($categoryData['parentID'])) {
                continue;
            }

            $category = Category::where(
                'olx_id',
                $categoryData['id']
            )->first();

            $parent = Category::where(
                'olx_id',
                $categoryData['parentID']
            )->first();

            if ($category && $parent) {
                $category->update([
                    'parent_id' => $parent->id,
                ]);
            }
        }

        $fieldResponses = [];
        $commonFieldsSeeded = false;

        foreach ($flattenedCategories as $categoryData) {
            $externalId = $categoryData['external_id'];

            $fieldResponse = $olxApiService->getCategoryFields($externalId);

            $categoryFieldData = $fieldResponse[$externalId] ?? null;

            if ($categoryFieldData) {
                $fieldResponses[$externalId] = $categoryFieldData;

                $this->saveFields($categoryFieldData);
            }

            if (
                ! $commonFieldsSeeded &&
                isset($fieldResponse['common_category_fields'])
            ) {
                $commonFieldData = $fieldResponse['common_category_fields'];

                $this->saveFields($commonFieldData);

                $fieldResponses['common_category_fields'] = $commonFieldData;

                $commonFieldsSeeded = true;
            }
        }

        foreach ($fieldResponses as $categoryFieldData) {
            $this->resolveFieldRelationships($categoryFieldData);
        }
    }
}

<?php

namespace App\Actions;

use App\Models\Ad;
use App\Models\CategoryField;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAd
{
    public function execute(User $user, array $data): Ad
    {
        return DB::transaction(function () use ($user, $data) {
            $ad = new Ad;

            $ad->user_id = $user->id;
            $ad->category_id = $data['category_id'];
            $ad->title = $data['title'];
            $ad->description = $data['description'];
            $ad->price = $data['price'];

            $ad->save();

            $categoryFields = CategoryField::where(
                'category_id',
                $ad->category_id
            )->get()->keyBy('attribute');

            foreach ($data['fields'] ?? [] as $attribute => $value) {
                $field = $categoryFields->get($attribute);

                if (! $field) {
                    continue;
                }

                if ($field->value_type === 'enum') {
                    $ad->fieldValues()->create([
                        'category_field_id' => $field->id,
                        'category_field_option_id' => $value,
                        'value' => null,
                    ]);

                    continue;
                }

                if ($field->value_type === 'enum_multiple') {
                    foreach ($value as $optionId) {
                        $ad->fieldValues()->create([
                            'category_field_id' => $field->id,
                            'category_field_option_id' => $optionId,
                            'value' => null,
                        ]);
                    }

                    continue;
                }

                $ad->fieldValues()->create([
                    'category_field_id' => $field->id,
                    'category_field_option_id' => null,
                    'value' => (string) $value,
                ]);
            }

            return $ad;
        });
    }
}

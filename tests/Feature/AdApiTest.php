<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_ad_with_dynamic_fields(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $category = Category::create([
            'olx_id' => 1000,
            'olx_external_id' => 'test-category',
            'name' => 'Test Cars',
            'slug' => 'test-cars',
            'level' => 1,
            'display_priority' => 1,
            'purpose' => 'for-sale',
        ]);

        $brandField = CategoryField::create([
            'olx_id' => 2000,
            'category_id' => $category->id,
            'attribute' => 'make',
            'name' => 'Brand',
            'value_type' => 'enum',
            'filter_type' => 'single_choice',
            'is_mandatory' => true,
            'state' => 'active',
        ]);

        $modelField = CategoryField::create([
            'olx_id' => 2001,
            'category_id' => $category->id,
            'parent_field_id' => $brandField->id,
            'attribute' => 'model',
            'name' => 'Model',
            'value_type' => 'enum',
            'filter_type' => 'single_choice',
            'is_mandatory' => true,
            'state' => 'active',
        ]);

        $honda = CategoryFieldOption::create([
            'olx_id' => 3000,
            'category_field_id' => $brandField->id,
            'value' => 'honda',
            'label' => 'Honda',
            'slug' => 'honda',
        ]);

        $accord = CategoryFieldOption::create([
            'olx_id' => 3001,
            'category_field_id' => $modelField->id,
            'parent_option_id' => $honda->id,
            'value' => 'accord',
            'label' => 'Accord',
            'slug' => 'accord',
        ]);

        $response = $this->postJson('/api/v1/ads', [
            'category_id' => $category->id,
            'title' => 'Honda Accord',
            'description' => 'Test classified ad',
            'price' => 12000,
            'fields' => [
                'make' => $honda->id,
                'model' => $accord->id,
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('ads', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Honda Accord',
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'category_field_id' => $brandField->id,
            'category_field_option_id' => $honda->id,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'category_field_id' => $modelField->id,
            'category_field_option_id' => $accord->id,
        ]);
    }

    public function test_invalid_dependent_option_returns_validation_error(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $category = Category::create([
            'olx_id' => 1000,
            'olx_external_id' => 'test-category',
            'name' => 'Test Cars',
            'slug' => 'test-cars',
            'level' => 1,
            'display_priority' => 1,
            'purpose' => 'for-sale',
        ]);

        $brandField = CategoryField::create([
            'olx_id' => 2000,
            'category_id' => $category->id,
            'attribute' => 'make',
            'name' => 'Brand',
            'value_type' => 'enum',
            'filter_type' => 'single_choice',
            'is_mandatory' => true,
            'state' => 'active',
        ]);

        $modelField = CategoryField::create([
            'olx_id' => 2001,
            'category_id' => $category->id,
            'parent_field_id' => $brandField->id,
            'attribute' => 'model',
            'name' => 'Model',
            'value_type' => 'enum',
            'filter_type' => 'single_choice',
            'is_mandatory' => true,
            'state' => 'active',
        ]);

        $honda = CategoryFieldOption::create([
            'olx_id' => 3000,
            'category_field_id' => $brandField->id,
            'value' => 'honda',
            'label' => 'Honda',
            'slug' => 'honda',
        ]);

        $mini = CategoryFieldOption::create([
            'olx_id' => 3001,
            'category_field_id' => $brandField->id,
            'value' => 'mini',
            'label' => 'MINI',
            'slug' => 'mini',
        ]);

        $cooper = CategoryFieldOption::create([
            'olx_id' => 3002,
            'category_field_id' => $modelField->id,
            'parent_option_id' => $mini->id,
            'value' => 'cooper-s',
            'label' => 'Cooper S',
            'slug' => 'cooper-s',
        ]);

        $response = $this->postJson('/api/v1/ads', [
            'category_id' => $category->id,
            'title' => 'Invalid Car',
            'description' => 'Testing invalid dependency',
            'price' => 12000,
            'fields' => [
                'make' => $honda->id,
                'model' => $cooper->id,
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fields.model',
            ]);

        $this->assertDatabaseMissing('ads', [
            'title' => 'Invalid Car',
        ]);
    }

    public function test_authenticated_user_can_create_ad_with_multiple_enum_values(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $category = Category::create([
            'olx_id' => 4000,
            'olx_external_id' => 'test-services',
            'name' => 'Test Services',
            'slug' => 'test-services',
            'level' => 1,
            'display_priority' => 1,
            'purpose' => 'for-sale',
        ]);

        $languagesField = CategoryField::create([
            'olx_id' => 5000,
            'category_id' => $category->id,
            'attribute' => 'languages',
            'name' => 'Languages',
            'value_type' => 'enum_multiple',
            'filter_type' => 'multiple_choice',
            'is_mandatory' => true,
            'state' => 'active',
        ]);

        $english = CategoryFieldOption::create([
            'olx_id' => 6000,
            'category_field_id' => $languagesField->id,
            'value' => 'english',
            'label' => 'English',
            'slug' => 'english',
        ]);

        $arabic = CategoryFieldOption::create([
            'olx_id' => 6001,
            'category_field_id' => $languagesField->id,
            'value' => 'arabic',
            'label' => 'Arabic',
            'slug' => 'arabic',
        ]);

        $response = $this->postJson('/api/v1/ads', [
            'category_id' => $category->id,
            'title' => 'Translation Service',
            'description' => 'Arabic and English translation service',
            'price' => 100,
            'fields' => [
                'languages' => [
                    $english->id,
                    $arabic->id,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.fields.0.attribute',
                'languages'
            )
            ->assertJsonPath(
                'data.fields.0.value.0',
                'English'
            )
            ->assertJsonPath(
                'data.fields.0.value.1',
                'Arabic'
            );

        $ad = Ad::where('title', 'Translation Service')->firstOrFail();

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'category_field_id' => $languagesField->id,
            'category_field_option_id' => $english->id,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'category_field_id' => $languagesField->id,
            'category_field_option_id' => $arabic->id,
        ]);

        $this->assertSame(
            2,
            $ad->fieldValues()
                ->where('category_field_id', $languagesField->id)
                ->count()
        );
    }
}

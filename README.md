# Dynamic Classified Ads API

This project is a Laravel REST API for a classified ads platform.

The main challenge was that ads from different categories don't follow the same structure. A car listing, for example, needs a brand, model, mileage, year, fuel type, etc., while another category can have completely different fields.

Because of that, I built the API around dynamic category fields instead of hardcoding these attributes into the `ads` table.

## External Data

The categories and their fields are fetched from OLX and stored locally.

One issue I faced early on was that the API references initially provided for the task were not returning the expected data. I didn't want to replace them with hardcoded categories because that would defeat the purpose of the assignment.

I inspected how the OLX frontend was getting this data and traced the endpoints currently used by the frontend through its JavaScript and network requests. I then used those endpoints in `OlxApiService`.

The seeder takes care of importing:

- categories and their hierarchy
- fields belonging to each category
- field types and constraints
- available choices
- multiple-choice fields
- relationships between fields and choices

`updateOrCreate()` is used during the import so rerunning the seeder updates existing records instead of creating duplicates.

## Database Structure

I kept the `ads` table limited to information that every ad has:

- user
- category
- title
- description
- price

I didn't add things such as `brand`, `model`, `mileage`, etc. directly to this table because those only make sense for certain categories.

Instead, the dynamic part is handled through:

- `category_fields` — defines what fields a category has
- `category_field_options` — stores the possible choices for enum and multiple-choice fields
- `ad_field_values` — stores the actual values selected or entered for an ad

This means that if the external API adds a new field to a category, I don't need to add another column to `ads` or create a new migration just to support it.

The same structure is also used for dependent fields.

For example:

    Brand: Honda
    └── Model: Accord

A field can reference its parent field, and an option can reference its parent option. This lets the API understand that Accord belongs to Honda instead of treating Brand and Model as two unrelated dropdowns.

The structure also supports fields where more than one option can be selected. For those fields, each selected option is stored separately while still belonging to the same ad and category field.

## Validation

Ad creation is validated through `StoreAdRequest`.

Once a category is selected, its active fields are loaded from the database and the validation rules are built from those definitions.

The request can automatically handle:

- required and optional fields
- integers and numeric values
- strings
- single enum choices
- multiple enum choices
- minimum and maximum values
- minimum and maximum lengths
- category-specific choices
- dependent choices

For multiple-choice fields, the API expects an array of option IDs. Each option is validated separately to make sure it belongs to the correct category field, and duplicate selections are rejected.

For example, Honda + Accord is valid, while Honda + Cooper S is rejected because Cooper S belongs to MINI.

Validation errors are returned as JSON with status `422`.

Authentication and authorization errors are also returned as JSON using `401` and `403`.

## Authentication

I used Laravel Sanctum for the protected endpoints.

Authentication is required for:

POST `/api/v1/ads`  
GET `/api/v1/my-ads`

The token is passed as a Bearer token in the request:

`Authorization: Bearer <token>`

For this assessment, I kept Sanctum's default token expiration behavior.

In a production version, I would configure an appropriate token expiration and revocation policy depending on how the API is being consumed rather than leaving personal access tokens valid indefinitely.

## API Endpoints

### POST `/api/v1/ads`

Creates a new ad for the authenticated user.

The request is validated against the fields of the selected category before anything is stored.

Example:

    {
        "category_id": 2,
        "title": "Honda Accord for Sale",
        "description": "Used Honda Accord in good condition",
        "price": 12000,
        "fields": {
            "make": 16,
            "model": 6569,
            "new_used": 103,
            "mileage": 65000,
            "year": 2020
        }
    }

A multiple-choice field can be submitted as an array:

    {
        "fields": {
            "languages": [10, 12]
        }
    }

A successful request returns `201 Created`.

### GET `/api/v1/my-ads`

Returns the authenticated user's ads.

The result is paginated and returned through `AdResource`.

### GET `/api/v1/ads/{ad}`

Returns one ad together with its category and dynamic field values.

I used an API Resource so the response exposes useful values such as:

    {
        "attribute": "make",
        "name": "Brand",
        "value": "Honda"
    }

Multiple-choice fields are grouped into a single response value:

    {
        "attribute": "languages",
        "name": "Languages",
        "value": ["English", "Arabic"]
    }

instead of exposing the internal database structure.

## Caching

Fetching the fields for every OLX category involves a large number of external requests, and this data does not change frequently enough to justify fetching it again every time the seeder runs.

I added caching inside `OlxApiService` for:

`olx.categories`

`olx.category_fields.{externalCategoryId}`

Each response is cached for 24 hours.

I chose a daily cache because category metadata is relatively stable, and it also follows the assessment requirement for daily or manual cache invalidation.

If I need to force a fresh synchronization before the cache expires, the cache can be cleared manually using:

`php artisan cache:clear`

I also kept the caching inside the service rather than the seeder. This way, if another command or job uses `OlxApiService` later, it automatically gets the same caching behavior.

I tested the difference by running the full seeder twice:

First run: 69.71 seconds  
Cached run: 6.73 seconds

The second run still performs the local database synchronization, but most of the external HTTP requests are avoided.

## Project Structure

The main responsibilities are separated between:

- `OlxApiService` — communicates with OLX and handles caching
- `OlxDataSeeder` — imports and updates external data
- `StoreAdRequest` — builds and applies dynamic validation
- `CreateAd` — handles the ad creation transaction and dynamic field persistence
- `AdController` — handles the API requests
- `AdResource` — formats API responses

I kept the controller thin so that validation, external API logic, and database operations don't all end up in the same class.

## Setup

Install the dependencies:

`composer install`

Create the environment file:

`cp .env.example .env`

Generate the application key:

`php artisan key:generate`

Configure PostgreSQL in `.env`:

    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

The OLX integration uses:

`OLX_API_BASE_URL=https://api-prod.olx-dubizzle.com/api/v1`

The value is read through `config/services.php`, so the service itself does not hardcode environment-specific configuration.

Run the migrations:

`php artisan migrate`

Seed the OLX categories, fields, and options:

`php artisan db:seed --class=OlxDataSeeder`

Start the application:

`php artisan serve`

The API will be available locally at:

`http://127.0.0.1:8000`

For production, `APP_DEBUG` should be set to `false`.

## Tests

I added feature tests for the main dynamic ad creation flow.

The tests currently check that:

- an authenticated user can create an ad with dynamic fields
- the ad and its field values are actually saved
- an invalid parent/child option combination returns `422`
- an invalid ad is not inserted into the database
- multiple enum values can be validated, stored, and returned correctly

Run the full test suite with:

`php artisan test`

At the time of submission:

Tests: 3 passed  
Assertions: 15
```

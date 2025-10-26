## Quick context for AI coding agents

This repository is a Laravel 11 web application with a Filament admin panel (Filament v3.x) and Vite/Tailwind frontend assets. PHP >= 8.2 is required. The admin UI and many domain behaviors are implemented as Filament Resources and custom form components under `app/Filament`.

Keep edits small and focused. Prefer non-breaking changes and follow existing conventions used in `app/Filament/*` and `app/Models/*`.

## Big-picture architecture

-   Laravel monolith. Code is organized by framework conventions (controllers, models, resources, migrations).
-   Filament handles admin interfaces: resources live under `app/Filament/Resources/` and widgets under `app/Filament/Widgets/`.
-   Eloquent models are in `app/Models/`. Common domain examples: `Charge`, `Charger`, `ChargerLocation`, `Vehicle`, `User`.
-   Custom Filament form components and helpers are in `app/Filament/Forms/` (e.g., `BaseSelect`, `TodayDatePicker`, `ImageFileUpload`). Respect these wrappers rather than replacing them with raw Filament components.

Data flow example: `ChargeResource` (app/Filament/Resources/Panel/ChargeResource.php)

-   Form uses custom components and relationships: `BaseSelect::make('charger_location_id')->relationship(...)`
-   Table columns compute derived values (e.g., `losses`, `Consumption`) via `getStateUsing` closures that reference relations like `vehicle->typeVehicle`.
-   Filters include relationship `SelectFilter` and complex `Filter::make('Date')` with a `DatePicker` form and a `->query(...)` closure.

## Key files & directories to inspect first

-   `app/Filament/Resources/Panel/ChargeResource.php` — good example of Filament patterns (filters, computed columns, modifyQueryUsing for scoping by user roles).
-   `app/Filament/Forms/` — contains project-specific form components used across resources (e.g., `BaseSelect`, `NominalTextInput`).
-   `app/Filament/Widgets/` — dashboard widgets (e.g., `ChargeStats`).
-   `app/Models/` — domain models and relationships used throughout the UI.
-   `routes/web.php` and `routes/api.php` — routing; check for custom middleware or endpoints.
-   `config/*.php` — spot feature flags and 3rd-party config (Filament, fortress/shield etc.).

## Project-specific conventions & patterns

-   Filament is heavily customized: always check `app/Filament/Forms/*` for custom behaviors (masks, suffixes, reactive handlers) before introducing new UI components.
-   Relationship fields frequently use `->relationship(name: 'chargerLocation', modifyQueryUsing: fn(Builder $q) => ...)` and `->getOptionLabelFromRecordUsing(...)` — keep these call patterns when editing.
-   Role checks: the code uses role checks like `auth()->user()->hasRole('super_admin')` and `bezhansalleh/filament-shield` is present in composer.json. Respect permission logic and prefer adding Filament Shield policies rather than bypassing them.
-   Image paths use `asset('storage/' . $record->image)` and upload components write to `directory('images/charge')`. Ensure `php artisan storage:link` is considered when working with images.

## Build, run, and developer workflows

-   PHP dependencies: composer install
-   Node dependencies & assets: npm install
-   Run dev (Vite + hot): `npm run dev` (project `package.json` defines `dev: vite`)
-   Build production assets: `npm run build`
-   Common artisan commands used by scripts: `php artisan migrate`, `php artisan storage:link`, `php artisan filament:upgrade` (composer scripts run some of these automatically)

Example commands (run in project root):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run dev
```

Testing and linting

-   Tests live in `tests/` (Feature, Unit). Run tests with `./vendor/bin/phpunit` or `php artisan test`.
-   Laravel Pint is available for formatting: `./vendor/bin/pint` (or `composer exec -- ...`).

## Integration points & external deps

-   Filament (filament/filament), Livewire, Jetstream and Sanctum are used. Expect Livewire components in Filament resources.
-   3rd-party Filament plugins are present (currency, maps, shield). When changing Filament behavior, confirm plugin APIs in `composer.json`.
-   Image optimization uses `joshembling/image-optimizer` — avoid duplicating optimization steps without checking config.

## Editing guidance for agents

-   When touching Filament Resource files (e.g., `ChargeResource.php`):
    -   Preserve the resource registration shape (form/table/getPages/getRelations). Avoid moving code out of the resource unless you also update routes and service providers.
    -   Keep reactive form semantics: many fields use `->reactive()` and `->afterStateUpdated(...)` closures. Preserve callable signatures and `callable $set` usage.
    -   If you change queries, be mindful of `modifyQueryUsing` and `->modifyQueryUsing(function (Builder $query) {...})` which implement user scoping.

## Examples to follow / copy patterns from

-   Date filter pattern (used in `ChargeResource`): `Filter::make('Date')->form([DatePicker::make('date_from'), DatePicker::make('date_until')])->query(fn(Builder $query, array $data) => $query->when(...))`.
-   Calculated column pattern: `TextColumn::make('losses')->getStateUsing(function ($record) { ... })` — put non-trivial presentation logic here instead of in controllers.

## Agent DOs and DON'Ts

-   DO prefer small, incremental changes and run tests locally.
-   DO respect Filament patterns and reuse `app/Filament/Forms/*` helpers.
-   DO run `php artisan migrate` and `php artisan storage:link` when editing upload/storage-related code.
-   DON'T change database column names or model relationships without updating migrations, factories, and resources.
-   DON'T bypass role/permission checks; modify policy/shield config instead if needed.

If anything in this file is unclear or you want more examples (for example, additional resource walkthroughs like `ChargerResource` or `VehicleResource`), tell me which area to expand and I'll iterate.

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create the shared farm tables the portal reads from on the `farm` connection.
 *
 * In production these tables are owned and migrated by voerfit-ingest and
 * voerfit-bcs; the portal never migrates them. Tests get a minimal copy that
 * holds only the columns the portal uses.
 */
function createFarmSchema(): void
{
    $schema = Schema::connection('farm');

    $schema->create('ingest_animal', function (Blueprint $table) {
        $table->id();
        $table->string('life_number', 32)->unique();
        $table->bigInteger('user_number')->nullable();
    });

    $schema->create('core_cow_daily_summary', function (Blueprint $table) {
        $table->date('time');
        $table->integer('animal_id');
        $table->string('life_number', 32)->nullable();
        $table->integer('days_in_milk')->nullable();
        $table->integer('lactation_number')->nullable();
        $table->boolean('is_lactating')->nullable();
        $table->double('interval_milk_production')->nullable();
        $table->double('bcs')->nullable();
        $table->double('bcs_sem')->nullable();
        $table->double('feed_total_sum_n1')->nullable();
        $table->double('feed_total_sum_n3')->nullable();
        $table->double('feed_total_original_sum_n1')->nullable();
        $table->double('feed_total_original_sum_n3')->nullable();
        $table->integer('feed_rest_last_n1')->nullable();
        $table->integer('feed_rest_last_n3')->nullable();
        $table->integer('mdp_milkings')->nullable();
        $table->integer('mdp_refusals')->nullable();
        $table->double('lab_milk_quantity_mass')->nullable();
        $table->double('lab_fat_percentage')->nullable();
        $table->double('lab_protein_percentage')->nullable();
        $table->double('lab_lactose_percentage')->nullable();
        $table->double('lab_ureum')->nullable();
        $table->integer('lab_cell_number')->nullable();
        $table->date('lab_sample_date')->nullable();
        $table->primary(['time', 'animal_id']);
    });

    $schema->create('core_animal_summary', function (Blueprint $table) {
        $table->integer('animal_id')->primary();
        $table->string('life_number', 32)->nullable();
        $table->string('name')->nullable();
        $table->bigInteger('herd_id')->nullable();
    });

    $schema->create('agrovision_animal', function (Blueprint $table) {
        $table->integer('animal_id')->primary();
        $table->string('registration_id', 32)->nullable();
    });

    $schema->create('agrovision_milk_sample', function (Blueprint $table) {
        $table->id();
        $table->integer('animal_id');
        $table->date('date');
        $table->double('milk_quantity_mass')->nullable();
        $table->double('fat_percentage')->nullable();
        $table->double('protein_percentage')->nullable();
        $table->double('lactose_percentage')->nullable();
        $table->double('cell_value')->nullable();
        $table->double('urea')->nullable();
    });

    $schema->create('voerfit_rule_values', function (Blueprint $table) {
        $table->string('name')->primary();
        $table->string('value');
        $table->string('source')->nullable();
    });

    $schema->create('feed_engine_release_hold', function (Blueprint $table) {
        $table->id();
        $table->integer('animal_id');
        $table->integer('feed_number');
        $table->decimal('hold_kg', 5, 2)->nullable();
        $table->decimal('last_applied_kg', 5, 2)->nullable();
        $table->date('last_applied_date')->nullable();
        $table->boolean('active')->default(true);
    });

    $schema->create('experiment_controlgroupassignment', function (Blueprint $table) {
        $table->id();
        $table->integer('animal_id');
        $table->string('experiment_id');
        $table->boolean('active')->default(true);
        $table->boolean('belongs_to_test_group')->default(true);
        $table->double('delta_feed')->nullable();
    });

    $schema->create('core_camerasnapshot', function (Blueprint $table) {
        $table->id();
        $table->integer('animal_id');
        $table->dateTime('time');
        $table->string('gcs_path');
        $table->string('label');
    });
}

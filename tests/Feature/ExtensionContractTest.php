<?php

use App\Http\Controllers\CRUDBooster\TestRelationController;
use crocodicstudio\crudbooster\controllers\CBController;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Support\Facades\Route;

it('preserves the public CBController configuration contract', function (): void {
    $controller = new TestRelationController;
    $controller->cbInit();

    expect($controller)
        ->toBeInstanceOf(CBController::class)
        ->and($controller->table)->toBe('test_relation_modules')
        ->and($controller->form)->toHaveCount(1)
        ->and($controller->form[0]['name'])->toBe('children')
        ->and($controller->form[0]['columns'][0]['datatable'])->toBe('test_related_records,label')
        ->and($controller->form[0]['columns'][1]['datamodal_table'])->toBe('test_related_records')
        ->and($controller->form[0]['columns'][1]['datamodal_select_to'])->toBe('category_id:category_field');
});

it('preserves dynamic route names, methods, and legacy delete exclusion', function (): void {
    Route::group([
        'prefix' => config('crudbooster.ADMIN_PATH'),
        'namespace' => 'App\Http\Controllers\CRUDBooster',
    ], function (): void {
        CRUDBooster::routeController('relation-test', class_basename(TestRelationController::class));
    });

    $deleteRoute = Route::getRoutes()->getByName('TestRelationControllerPostDelete');

    expect($deleteRoute)
        ->not->toBeNull()
        ->and($deleteRoute->methods())->toBe(['POST'])
        ->and($deleteRoute->uri())->toBe('admin/relation-test/delete/{one?}/{two?}/{three?}/{four?}/{five?}')
        ->and(Route::has('TestRelationControllerGetDeleteLegacy'))->toBeFalse();
});

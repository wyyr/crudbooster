<?php

use App\Http\Controllers\CRUDBooster\TestCrudController;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

function bindCrudRequest(string $uri, string $method = 'GET', array $input = []): void
{
    $request = Request::create($uri, $method, $input);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);
}

function bindCrudAction(string $uri, string $action, string $method = 'GET'): void
{
    $request = Request::create($uri, $method);
    $request->setLaravelSession(app('session')->driver());

    $route = new RoutingRoute($method, trim((string) parse_url($uri, PHP_URL_PATH), '/'), [
        'uses' => $action,
        'controller' => $action,
    ]);
    $route->bind($request);
    $request->setRouteResolver(fn (): RoutingRoute => $route);
    app()->instance('request', $request);

    $currentRoute = new ReflectionProperty(Router::class, 'current');
    $currentRoute->setValue(app('router'), $route);
}

beforeEach(function (): void {
    Route::get('/test-notification-latest', fn (): string => '')->name('NotificationControllerGetLatestJson');
    Route::get('/test-notification-index', fn (): string => '')->name('NotificationControllerGetIndex');

    Schema::create('cms_moduls', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('icon')->nullable();
        $table->string('path')->nullable();
        $table->string('table_name')->nullable();
        $table->string('controller')->nullable();
        $table->boolean('is_protected')->default(0);
        $table->timestamp('deleted_at')->nullable();
    });
    Schema::create('cms_privileges', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->boolean('is_superadmin')->nullable();
        $table->string('theme_color')->nullable();
    });
    Schema::create('cms_privileges_roles', function (Blueprint $table): void {
        $table->id();
        $table->boolean('is_visible')->nullable();
        $table->boolean('is_create')->nullable();
        $table->boolean('is_read')->nullable();
        $table->boolean('is_edit')->nullable();
        $table->boolean('is_delete')->nullable();
        $table->unsignedBigInteger('id_cms_privileges')->nullable();
        $table->unsignedBigInteger('id_cms_moduls')->nullable();
        $table->string('path')->nullable();
        $table->string('name')->nullable();
    });
    Schema::create('test_crud_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('status')->nullable();
        $table->string('photo')->nullable();
        $table->timestamps();
    });
    Schema::create('test_crud_notes', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('record_id');
        $table->string('body');
    });

    DB::table('cms_moduls')->insert([
        'name' => 'CRUD Test',
        'icon' => 'fa fa-cube',
        'path' => 'crud-test',
        'table_name' => 'test_crud_records',
        'controller' => 'TestCrudController',
    ]);

    Route::group([
        'prefix' => config('crudbooster.ADMIN_PATH'),
        'namespace' => 'App\Http\Controllers\CRUDBooster',
    ], function (): void {
        CRUDBooster::routeController('crud-test', class_basename(TestCrudController::class));
    });
});

it('registers the CRUD module dynamic routes with expected names, verbs, and URIs', function (): void {
    $routes = [
        'TestCrudControllerGetIndex' => ['GET', 'admin/crud-test'],
        'TestCrudControllerGetAdd' => ['GET', 'admin/crud-test/add/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostAddSave' => ['POST', 'admin/crud-test/add-save/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetEdit' => ['GET', 'admin/crud-test/edit/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostEditSave' => ['POST', 'admin/crud-test/edit-save/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostDelete' => ['POST', 'admin/crud-test/delete/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetDetail' => ['GET', 'admin/crud-test/detail/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetImportData' => ['GET', 'admin/crud-test/import-data/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostDoUploadImportData' => ['POST', 'admin/crud-test/do-upload-import-data/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostExportData' => ['POST', 'admin/crud-test/export-data/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostUploadFile' => ['POST', 'admin/crud-test/upload-file/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetDataTable' => ['GET', 'admin/crud-test/data-table/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetModalData' => ['GET', 'admin/crud-test/modal-data/{one?}/{two?}/{three?}/{four?}/{five?}'],
    ];

    foreach ($routes as $name => [$verb, $uri]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($verb)
            ->and($route->uri())->toBe($uri);
    }

    expect(Route::has('TestCrudControllerGetDelete'))->toBeFalse();
});

it('renders add, edit, detail, and import views for a superadmin subclass controller', function (): void {
    Session::put('admin_id', 1);
    Session::put('admin_is_superadmin', true);

    $id = DB::table('test_crud_records')->insertGetId([
        'name' => 'Existing',
        'status' => 'open',
    ]);

    bindCrudAction('/admin/crud-test/add', TestCrudController::class.'@getAdd');
    $add = app(TestCrudController::class)->getAdd();

    bindCrudAction('/admin/crud-test/edit/'.$id, TestCrudController::class.'@getEdit');
    $edit = app(TestCrudController::class)->getEdit($id);

    bindCrudAction('/admin/crud-test/detail/'.$id, TestCrudController::class.'@getDetail');
    $detail = app(TestCrudController::class)->getDetail($id);

    bindCrudAction('/admin/crud-test/import-data', TestCrudController::class.'@getImportData');
    $import = app(TestCrudController::class)->getImportData();

    expect($add)->toBeInstanceOf(View::class)
        ->and($add->name())->toBe('crudbooster::default.form')
        ->and($add->getData()['command'])->toBe('add')
        ->and(view()->shared('forms')[0]['name'])->toBe('name')
        ->and(view()->shared('forms')[2]['type'])->toBe('child')
        ->and($edit)->toBeInstanceOf(View::class)
        ->and($edit->getData()['command'])->toBe('edit')
        ->and($edit->getData()['row']->name)->toBe('Existing')
        ->and($detail)->toBeInstanceOf(View::class)
        ->and($detail->getData()['command'])->toBe('detail')
        ->and($import)->toBeInstanceOf(View::class)
        ->and($import->name())->toBe('crudbooster::import');
});

it('assigns valid form input and accepts configured validation rules', function (): void {
    Session::put('admin_id', 1);
    Session::put('admin_is_superadmin', true);
    bindCrudRequest('/admin/crud-test/add-save', 'POST', [
        'name' => 'Widget',
        'status' => 'open',
    ]);

    $controller = app(TestCrudController::class);
    $controller->cbLoader();
    $controller->validation();
    $controller->input_assignment();

    expect($controller->arr['name'])->toBe('Widget')
        ->and($controller->arr['status'])->toBe('open')
        ->and(array_key_exists('notes', $controller->arr))->toBeFalse();
});

it('denies create, read, update, and delete when the role has no module privileges', function (): void {
    Session::put('admin_id', 2);
    Session::put('admin_is_superadmin', false);
    Session::put('admin_privileges_roles', []);
    bindCrudRequest('/admin/crud-test/add');

    expect(CRUDBooster::isCreate())->toBeFalsy()
        ->and(CRUDBooster::isRead())->toBeFalsy()
        ->and(CRUDBooster::isUpdate())->toBeFalsy()
        ->and(CRUDBooster::isDelete())->toBeFalsy()
        ->and(CRUDBooster::isView())->toBeFalsy();
});

it('grants module privileges from the session role matching the current path', function (): void {
    Session::put('admin_id', 2);
    Session::put('admin_is_superadmin', false);
    Session::put('admin_privileges_roles', [
        (object) [
            'path' => 'crud-test',
            'is_visible' => true,
            'is_create' => true,
            'is_read' => true,
            'is_edit' => false,
            'is_delete' => false,
        ],
    ]);
    bindCrudRequest('/admin/crud-test/add');

    expect(CRUDBooster::isCreate())->toBeTrue()
        ->and(CRUDBooster::isRead())->toBeTrue()
        ->and(CRUDBooster::isUpdate())->toBeFalse()
        ->and(CRUDBooster::isDelete())->toBeFalse()
        ->and(CRUDBooster::isView())->toBeTrue();
});

it('stores uploaded files on the configured disk without touching the host filesystem', function (): void {
    Storage::fake('local');
    config()->set('crudbooster.filesystem_driver', 'local');
    config()->set('crudbooster.prefix_upload', '');
    Session::put('admin_id', 7);

    $request = Request::create('/admin/crud-test/upload-file', 'POST', [], [], [
        'userfile' => UploadedFile::fake()->create('notes.txt', 12, 'text/plain'),
    ]);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);

    $path = CRUDBooster::uploadFile('userfile', true);

    expect($path)->not->toBeNull()
        ->and($path)->toContain('uploads/7/');
    Storage::disk('local')->assertExists($path);
});

it('rejects import uploads that are not spreadsheet files', function (): void {
    Session::put('admin_id', 1);
    Session::put('admin_is_superadmin', true);

    $response = $this->withoutMiddleware()
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->from('/admin/crud-test/import-data')
        ->post(route('TestCrudControllerPostDoUploadImportData'), [
            'userfile' => UploadedFile::fake()->create('notes.txt', 12, 'text/plain'),
        ]);

    $response->assertRedirect('/admin/crud-test/import-data')
        ->assertSessionHas('message_type', 'warning');
});

it('accepts spreadsheet import uploads onto an isolated disk', function (): void {
    Storage::fake('local');
    Session::put('admin_id', 1);
    Session::put('admin_is_superadmin', true);

    $response = $this->withoutMiddleware()
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostDoUploadImportData'), [
            'userfile' => UploadedFile::fake()->create('records.xlsx', 20, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('import-data?file=');

    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
    Storage::disk('local')->assertExists(base64_decode($query['file']));
});

it('logs in a valid user and stores privilege session keys', function (): void {
    Schema::table('cms_users', function (Blueprint $table): void {
        $table->string('photo')->nullable();
    });
    DB::table('cms_privileges')->insert([
        'name' => 'Superadmin',
        'is_superadmin' => 1,
        'theme_color' => 'skin-blue',
    ]);
    DB::table('cms_privileges_roles')->insert([
        'is_visible' => 1,
        'is_create' => 1,
        'is_read' => 1,
        'is_edit' => 1,
        'is_delete' => 1,
        'id_cms_privileges' => 1,
        'id_cms_moduls' => 1,
        'path' => 'crud-test',
        'name' => 'CRUD Test',
    ]);
    DB::table('cms_users')->insert([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('secret123'),
        'id_cms_privileges' => 1,
        'status' => 'Active',
    ]);

    $this->from(route('getLogin'))
        ->post(route('postLogin'), [
            'email' => 'admin@example.test',
            'password' => 'secret123',
        ])
        ->assertRedirect();

    expect(session('admin_id'))->toBe(1)
        ->and(session('admin_is_superadmin'))->toBe(1)
        ->and(session('admin_name'))->toBe('Admin')
        ->and(session('admin_privileges'))->toBe(1);
});

it('rejects invalid login credentials without creating a session', function (): void {
    DB::table('cms_privileges')->insert([
        'name' => 'Superadmin',
        'is_superadmin' => 1,
        'theme_color' => 'skin-blue',
    ]);
    DB::table('cms_users')->insert([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('secret123'),
        'id_cms_privileges' => 1,
        'status' => 'Active',
    ]);

    $this->from(route('getLogin'))
        ->post(route('postLogin'), [
            'email' => 'admin@example.test',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('getLogin'))
        ->assertSessionHas('message');

    expect(session('admin_id'))->toBeNull();
});

it('redirects guests away from protected backend routes', function (): void {
    $this->get('/'.config('crudbooster.ADMIN_PATH').'/api_generator')
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');
});

<?php

use App\Http\Controllers\CRUDBooster\TestCrudController;
use App\Http\Controllers\CRUDBooster\TestCrudUuidController;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use crocodicstudio\crudbooster\imports\DefaultImportExcel;
use crocodicstudio\crudbooster\middlewares\CBBackend;
use crocodicstudio\crudbooster\uploads\DeleteUploadedFileAction;
use crocodicstudio\crudbooster\uploads\UploadFileAction;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

function bindCrudRequest(string $uri, string $method = 'GET', array $input = []): void
{
    $request = Request::create($uri, $method, $input);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);
}

function bindCrudAction(string $uri, string $action, string $method = 'GET', array $files = []): void
{
    $request = Request::create($uri, $method, [], [], $files);
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

/**
 * @param  array<int, string>  $headers
 * @param  array<int, array<int, string>>  $rows
 * @param  array<int, string>  $mapping
 * @return array{0: string, 1: string}
 */
function prepareImportCsv(array $headers, array $rows, array $mapping): array
{
    $temporaryStoragePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'crudbooster-import-'.bin2hex(random_bytes(8));
    app()->useStoragePath($temporaryStoragePath);
    config(['filesystems.disks.local.root' => storage_path('app/private')]);
    Storage::forgetDisk('local');

    $relativeFilePath = 'uploads/import/records.csv';
    $absoluteFilePath = Storage::disk('local')->path($relativeFilePath);
    File::ensureDirectoryExists(dirname($absoluteFilePath));
    $handle = fopen($absoluteFilePath, 'wb');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    $file = base64_encode($relativeFilePath);
    bindCrudAction('/admin/crud-test/import-data?file='.$file, TestCrudController::class.'@getImportData');
    $preview = app(TestCrudController::class)->getImportData();

    expect($preview)->toBeInstanceOf(View::class)
        ->and($preview->name())->toBe('crudbooster::import')
        ->and($preview->getData()['data_import_column'])->toBe(array_map(
            static fn (string $header): string => Str::snake($header),
            $headers,
        ))
        ->and(Session::get('total_data_import'))->toBe(count($rows));

    bindCrudAction(
        '/admin/crud-test/done-import?file='.$file.'&import=1',
        TestCrudController::class.'@postDoneImport',
        'POST',
    );
    request()->merge(['select_column' => $mapping]);
    $mappedView = app(TestCrudController::class)->postDoneImport();

    expect($mappedView)->toBeInstanceOf(View::class)
        ->and($mappedView->name())->toBe('crudbooster::import')
        ->and(Session::get('select_column'))->toBe($mapping);

    return [$temporaryStoragePath, $file];
}

function addSqliteExportSchemaMetadata(): void
{
    DB::statement("ATTACH DATABASE ':memory:' AS information_schema");
    DB::statement('CREATE TABLE information_schema.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT, DATA_TYPE TEXT)');

    $metadata = [];
    foreach (Schema::getColumnListing('test_crud_records') as $column) {
        $metadata[] = [
            'TABLE_SCHEMA' => config('crudbooster.MAIN_DB_DATABASE'),
            'TABLE_NAME' => 'test_crud_records',
            'COLUMN_NAME' => $column,
            'DATA_TYPE' => 'text',
        ];
    }

    DB::table('information_schema.COLUMNS')->insert($metadata);
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
    Schema::create('test_crud_uuid_records', function (Blueprint $table): void {
        $table->string('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
    Schema::create('test_crud_notes', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('record_id');
        $table->string('body');
    });
    Schema::create('test_crud_categories', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
    Schema::create('test_crud_record_category', function (Blueprint $table): void {
        $table->unsignedBigInteger('test_crud_records_id');
        $table->unsignedBigInteger('test_crud_categories_id');
        $table->foreign('test_crud_records_id')->references('id')->on('test_crud_records');
        $table->foreign('test_crud_categories_id')->references('id')->on('test_crud_categories');
    });

    DB::table('cms_moduls')->insert([
        'name' => 'CRUD Test',
        'icon' => 'fa fa-cube',
        'path' => 'crud-test',
        'table_name' => 'test_crud_records',
        'controller' => 'TestCrudController',
    ]);
    DB::table('cms_moduls')->insert([
        'name' => 'CRUD UUID Test',
        'icon' => 'fa fa-cube',
        'path' => 'crud-uuid-test',
        'table_name' => 'test_crud_uuid_records',
        'controller' => 'TestCrudUuidController',
    ]);

    Route::group([
        'middleware' => ['web', CBBackend::class],
        'prefix' => config('crudbooster.ADMIN_PATH'),
        'namespace' => 'App\Http\Controllers\CRUDBooster',
    ], function (): void {
        CRUDBooster::routeController('crud-test', class_basename(TestCrudController::class));
        CRUDBooster::routeController('crud-uuid-test', class_basename(TestCrudUuidController::class));
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
        'TestCrudControllerPostDeleteImage' => ['POST', 'admin/crud-test/delete-image/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostUploadFile' => ['POST', 'admin/crud-test/upload-file/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerPostUploadSummernote' => ['POST', 'admin/crud-test/upload-summernote/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetDataTable' => ['GET', 'admin/crud-test/data-table/{one?}/{two?}/{three?}/{four?}/{five?}'],
        'TestCrudControllerGetModalData' => ['GET', 'admin/crud-test/modal-data/{one?}/{two?}/{three?}/{four?}/{five?}'],
    ];

    foreach ($routes as $name => [$verb, $uri]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($verb)
            ->and($route->uri())->toBe($uri);
    }

    expect(Route::getRoutes()->getByName('TestCrudControllerGetImportData')->gatherMiddleware())
        ->toContain('web', CBBackend::class)
        ->and(Route::getRoutes()->getByName('TestCrudControllerPostUploadFile')->gatherMiddleware())
        ->toContain('web', CBBackend::class)
        ->and(Route::getRoutes()->getByName('TestCrudControllerPostUploadSummernote')->gatherMiddleware())
        ->toContain('web', CBBackend::class)
        ->and(Route::getRoutes()->getByName('TestCrudControllerPostDeleteImage')->gatherMiddleware())
        ->toContain('web', CBBackend::class)
        ->and(Route::getRoutes()->getByName('fileControllerPreview')->gatherMiddleware())
        ->toContain('web')
        ->not->toContain(CBBackend::class)
        ->and(Route::getRoutes()->getByName('TestCrudControllerPostDoImportChunk')->gatherMiddleware())
        ->toContain('web', CBBackend::class);

    expect(Route::has('TestCrudControllerGetDelete'))->toBeFalse();
});

it('requires a backend session for import workflow routes', function (): void {
    $this->get(route('TestCrudControllerGetImportData'))
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');

    $this->post(route('TestCrudControllerPostDoImportChunk'), ['file' => base64_encode('missing.csv')])
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');
});

it('compiles import error handling without rendering row errors as HTML', function (): void {
    $template = File::get(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'import.blade.php');
    $compiledTemplate = Blade::compileString($template);

    expect($compiledTemplate)
        ->toContain('.text(message)')
        ->toContain('Import completed with row errors:')
        ->toContain('.fail(showImportFailure)');
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
        ->and(view()->shared('forms')[3]['type'])->toBe('child')
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

it('keeps form uploads on their configured field and preserves legacy size and MIME acceptance', function (): void {
    Storage::fake('local');
    config()->set('crudbooster.filesystem_driver', 'local');
    config()->set('crudbooster.prefix_upload', 'tenant');
    Session::put('admin_id', 7);
    config()->set('crudbooster.DEFAULT_UPLOAD_MAX_SIZE', 1);

    $request = Request::create('/admin/crud-test/add-save', 'POST', ['name' => 'Uploaded'], [], [
        'photo' => UploadedFile::fake()->create('oversized.txt', 2, 'application/pdf'),
        'not_configured' => UploadedFile::fake()->create('ignored.txt', 1, 'text/plain'),
    ]);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);

    $controller = app(TestCrudController::class);
    $controller->cbLoader();
    $controller->input_assignment();
    $path = $controller->arr['photo'];

    expect($path)->toMatch('~^tenant/uploads/7/\d{4}-\d{2}/oversized\.txt$~')
        ->and(array_key_exists('not_configured', $controller->arr))->toBeFalse();
    Storage::disk('local')->assertExists($path);
});

it('preserves overwrite behavior for non-encrypted file names', function (): void {
    Storage::fake('local');
    config()->set('crudbooster.filesystem_driver', 'local');
    config()->set('crudbooster.prefix_upload', '');
    Session::put('admin_id', 7);

    foreach (['first', 'second'] as $content) {
        $request = Request::create('/admin/crud-test/add-save', 'POST', [], [], [
            'userfile' => UploadedFile::fake()->createWithContent('same name.txt', $content),
        ]);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);

        $path = CRUDBooster::uploadFile('userfile', false, null, null, 7);
    }

    expect($path)->toBe('/uploads/7/'.date('Y-m').'/same_name.txt')
        ->and(Storage::disk('local')->get($path))->toBe('second');
});

it('requires a backend session for upload and image-delete routes', function (): void {
    $this->post(route('TestCrudControllerPostUploadFile'))
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');

    $this->post(route('TestCrudControllerPostUploadSummernote'))
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');

    $this->post(route('TestCrudControllerPostDeleteImage'))
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');
});

it('rejects image deletion for a field that is not configured as an upload', function (): void {
    $response = $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostDeleteImage'), [
            'id' => 1,
            'column' => 'status',
        ]);

    $response->assertNotFound();
});

it('uploads Summernote files with the legacy plain URL response and an encrypted unique path', function (): void {
    Storage::fake('local');
    config()->set('crudbooster.filesystem_driver', 'local');
    config()->set('crudbooster.prefix_upload', '');

    foreach ([
        ['upload-file', 'postUploadFile'],
        ['upload-summernote', 'postUploadSummernote'],
    ] as [$slug, $method]) {
        Session::put('admin_id', 7);
        bindCrudAction(
            '/admin/crud-test/'.$slug,
            TestCrudController::class.'@'.$method,
            'POST',
            ['userfile' => UploadedFile::fake()->createWithContent('notes.txt', 'editor body')],
        );

        ob_start();
        app(TestCrudController::class)->{$method}();
        $url = trim(ob_get_clean());
        expect($url)->toContain('/uploads/7/'.date('Y-m').'/')
            ->and($url)->toMatch('~/[a-f0-9]{32}\.txt$~');

        $path = substr(parse_url($url, PHP_URL_PATH), strlen('/storage/'));
        Storage::disk('local')->assertExists($path);
        expect(Storage::disk('local')->get($path))->toBe('editor body');
    }
});

it('serves uploaded preview files from the configured disk without backend authentication', function (): void {
    config()->set('filesystems.disks.preview', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/preview'),
    ]);
    Storage::fake('preview');
    config()->set('crudbooster.filesystem_driver', 'preview');
    Storage::disk('preview')->put('uploads/7/2026-03/notes.txt', 'preview body');

    $response = $this->get(route('fileControllerPreview', [
        'one' => '7',
        'two' => '2026-03',
        'three' => 'notes.txt',
    ]));

    $response->assertOk();
    ob_start();
    $response->baseResponse->sendContent();
    $content = ob_get_clean();
    expect($content)->toBe('preview body')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->headers->get('Content-Type'))->toContain('text/plain');

    $download = $this->get(route('fileControllerPreview', [
        'one' => '7',
        'two' => '2026-03',
        'three' => 'notes.txt',
        'download' => 1,
    ]));
    $download->assertOk()->assertDownload('notes.txt');
    ob_start();
    $download->baseResponse->sendContent();
    expect(ob_get_clean())->toBe('preview body');
});

it('serves image bytes and image metadata from the configured storage disk', function (): void {
    config()->set('filesystems.disks.preview', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/preview'),
    ]);
    Storage::fake('preview');
    config()->set('crudbooster.filesystem_driver', 'preview');
    $image = UploadedFile::fake()->image('pixel.png', 1, 1);
    $image = File::get($image->getRealPath());
    Storage::disk('preview')->put('uploads/7/2026-03/pixel.png', $image);

    $response = $this->get(route('fileControllerPreview', [
        'one' => '7',
        'two' => '2026-03',
        'three' => 'pixel.png',
    ]));

    $response->assertOk();
    expect($response->getContent())->toBe($image)
        ->and($response->headers->get('Content-Type'))->toContain('image/png')
        ->and((int) $response->headers->get('Content-Length'))->toBe(strlen($image));

    $thumbnail = $this->get(route('fileControllerPreview', [
        'one' => '7',
        'two' => '2026-03',
        'three' => 'pixel.png',
    ]).'?w=1&h=1');

    $thumbnail->assertOk();
    expect($thumbnail->getContent())->not->toBeEmpty()
        ->and($thumbnail->headers->get('Content-Type'))->toContain('image/png');
});

it('fails explicitly when storage refuses an uploaded file', function (): void {
    config()->set('crudbooster.filesystem_driver', 'local');
    config()->set('crudbooster.prefix_upload', '');
    Session::put('admin_id', 7);

    $request = Request::create('/admin/crud-test/upload-file', 'POST', [], [], [
        'userfile' => UploadedFile::fake()->create('notes.txt', 12, 'text/plain'),
    ]);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);

    $disk = $this->createMock(FilesystemAdapter::class);
    $disk->expects($this->once())->method('makeDirectory')->willReturn(true);
    $disk->expects($this->once())->method('putFileAs')->willReturn(false);
    $filesystem = $this->createMock(FilesystemManager::class);
    $filesystem->expects($this->once())->method('disk')->with('local')->willReturn($disk);

    expect(fn () => (new UploadFileAction($filesystem))->execute('userfile', true))
        ->toThrow(RuntimeException::class);
});

it('deletes existing uploads from the configured disk and tolerates missing files', function (): void {
    Storage::fake('local');
    config()->set('crudbooster.filesystem_driver', 'local');
    $path = 'uploads/7/2026-03/photo.png';
    Storage::disk('local')->put($path, 'image body');

    app(DeleteUploadedFileAction::class)->execute($path);
    Storage::disk('local')->assertMissing($path);
    app(DeleteUploadedFileAction::class)->execute($path);
});

it('does not clear an image field when storage deletion fails', function (): void {
    Session::put('admin_id', 1);
    Session::put('admin_is_superadmin', true);
    $imagePath = 'uploads/1/2026-03/photo.png';
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Image record',
        'photo' => $imagePath,
    ]);
    bindCrudAction('/admin/crud-test/delete-image', TestCrudController::class.'@postDeleteImage', 'POST');
    request()->merge(['id' => $recordId, 'column' => 'photo']);

    $disk = $this->createMock(FilesystemAdapter::class);
    $disk->expects($this->once())->method('exists')->with($imagePath)->willReturn(true);
    $disk->expects($this->once())->method('delete')->with($imagePath)->willReturn(false);
    $filesystem = $this->createMock(FilesystemManager::class);
    $filesystem->expects($this->once())->method('disk')->with('local')->willReturn($disk);
    app()->instance(
        DeleteUploadedFileAction::class,
        new DeleteUploadedFileAction($filesystem),
    );

    expect(fn () => app(TestCrudController::class)->postDeleteImage())
        ->toThrow(RuntimeException::class, 'Unable to delete the file');
    expect(DB::table('test_crud_records')->where('id', $recordId)->value('photo'))->toBe($imagePath);
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

it('previews a spreadsheet and imports selected columns into the configured table', function (): void {
    $temporaryStoragePath = null;
    try {
        $temporaryStoragePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'crudbooster-import-'.bin2hex(random_bytes(8));
        app()->useStoragePath($temporaryStoragePath);
        config(['filesystems.disks.local.root' => storage_path('app/private')]);
        Storage::forgetDisk('local');

        $uploadResponse = $this->withoutMiddleware()
            ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
            ->post(route('TestCrudControllerPostDoUploadImportData'), [
                'userfile' => UploadedFile::fake()->createWithContent(
                    'records.csv',
                    "Name,Status\n  Widget A  ,open\n,ignored\nWidget B,closed\n",
                ),
            ]);
        $uploadResponse->assertRedirect();

        parse_str((string) parse_url((string) $uploadResponse->headers->get('Location'), PHP_URL_QUERY), $query);
        $file = $query['file'] ?? null;
        expect($file)->toBeString();

        $relativeFilePath = base64_decode($file);
        expect(File::exists(Storage::disk('local')->path($relativeFilePath)))->toBeTrue();

        bindCrudAction('/admin/crud-test/import-data?file='.$file, TestCrudController::class.'@getImportData');
        $preview = app(TestCrudController::class)->getImportData();
        expect($preview->getData()['data_import_column'])->toBe(['name', 'status'])
            ->and(Session::get('total_data_import'))->toBe(3);

        bindCrudAction(
            '/admin/crud-test/done-import?file='.$file.'&import=1',
            TestCrudController::class.'@postDoneImport',
            'POST',
        );
        request()->merge(['select_column' => [1 => 'name', 2 => 'status']]);
        app(TestCrudController::class)->postDoneImport();

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 3,
            'select_column' => [
                1 => 'name',
                2 => 'status',
            ],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->orderBy('id')->get(['name', 'status'])->map(fn ($row): array => (array) $row)->all())
            ->toBe([
                ['name' => 'Widget A', 'status' => 'open'],
                ['name' => 'Widget B', 'status' => 'closed'],
            ])
            ->and(Session::get('select_column'))->toBe([1 => 'name', 2 => 'status'])
            ->and(File::exists(Storage::disk('local')->path($relativeFilePath)))->toBeTrue();
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('rejects invalid import mappings without writing partial rows', function (): void {
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            [['Valid row', 'open']],
            [99 => 'name'],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 1,
            'select_column' => [99 => 'name'],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertUnprocessable()
            ->assertExactJson([
                'status' => false,
                'message' => 'The selected import columns are invalid.',
            ]);

        expect(DB::table('test_crud_records')->count())->toBe(0);
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('rejects import mappings for headings that are not in the file', function (): void {
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            [['Valid row', 'open']],
            [1 => 'missing_heading'],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 1,
            'select_column' => [1 => 'missing_heading'],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertUnprocessable()
            ->assertExactJson([
                'status' => false,
                'message' => 'The selected import columns are invalid.',
            ]);

        expect(DB::table('test_crud_records')->count())->toBe(0);
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('rejects missing import mappings and invalid file tokens without writing rows', function (): void {
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            [['Valid row', 'open']],
            [],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 1,
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertUnprocessable()
            ->assertExactJson([
                'status' => false,
                'message' => 'The selected import columns are invalid.',
            ]);

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query([
                'file' => base64_encode('../outside.csv'),
            ]))
            ->assertUnprocessable()
            ->assertExactJson([
                'status' => false,
                'message' => 'The import file is invalid.',
            ]);

        expect(DB::table('test_crud_records')->count())->toBe(0);
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('rejects missing import files during preview', function (): void {
    $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->get(route('TestCrudControllerGetImportData').'?'.http_build_query([
            'file' => base64_encode('missing.csv'),
        ]))->assertNotFound();
});

it('imports files containing more than one spreadsheet chunk', function (): void {
    $temporaryStoragePath = null;

    try {
        $rows = [];
        for ($index = 1; $index <= 205; $index++) {
            $rows[] = ['Chunked '.$index, 'open'];
        }

        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            $rows,
            [1 => 'name', 2 => 'status'],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 205,
            'select_column' => [1 => 'name', 2 => 'status'],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->count())->toBe(205)
            ->and(DB::table('test_crud_records')->where('name', 'Chunked 205')->exists())->toBeTrue()
            ->and((new DefaultImportExcel)->chunkSize())->toBe(200);

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 205,
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query([
                'file' => $file,
                'resume' => 1,
            ]))
            ->assertOk()
            ->assertExactJson([
                'progress' => 100,
                'last_error' => null,
            ]);
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('preserves timestamp and password handling for selected import columns', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->string('password')->nullable();
    });

    $columns = Schema::getColumnListing('test_crud_records');
    $passwordColumn = array_search('password', $columns, true);
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status', 'Password'],
            [
                ['Plain record', 'open', ''],
                ['Protected record', 'closed', 'secret123'],
            ],
            [
                array_search('name', $columns, true) => 'name',
                array_search('status', $columns, true) => 'status',
                $passwordColumn => 'password',
            ],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 2,
            'select_column' => [
                array_search('name', $columns, true) => 'name',
                array_search('status', $columns, true) => 'status',
                $passwordColumn => 'password',
            ],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        $records = DB::table('test_crud_records')->orderBy('id')->get();
        expect($records)->toHaveCount(2)
            ->and($records[0]->password)->toBe('')
            ->and($records[0]->created_at)->not->toBeNull()
            ->and(Hash::check('secret123', $records[1]->password))->toBeTrue()
            ->and($records[1]->created_at)->not->toBeNull();
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('records row failures while continuing later imports and reporting progress', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->unique('name');
    });

    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            [
                ['Duplicate', 'open'],
                ['Duplicate', 'closed'],
                ['After failure', 'open'],
            ],
            [1 => 'name', 2 => 'status'],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 3,
            'select_column' => [1 => 'name', 2 => 'status'],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->orderBy('id')->pluck('name')->all())
            ->toBe(['Duplicate', 'After failure']);

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 3,
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query([
                'file' => $file,
                'resume' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('progress', 100)
            ->assertJsonPath('last_error', fn (?string $error): bool => $error !== null && str_contains($error, 'Duplicate'));
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('imports non-password fields when the target table has an optional password column', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->string('password')->nullable();
    });

    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Status'],
            [['No password mapping', 'open']],
            [1 => 'name', 2 => 'status'],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 1,
            'select_column' => [1 => 'name', 2 => 'status'],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->where('name', 'No password mapping')->value('password'))->toBeNull();
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('resolves foreign-key import labels to existing or newly created relation rows', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->unsignedBigInteger('test_related_records_id')->nullable();
    });
    DB::table('cms_moduls')->insert([
        'name' => 'Related Records',
        'path' => 'related-records',
        'table_name' => 'test_related_records',
        'controller' => 'TestRelatedRecordController',
    ]);

    $existingId = DB::table('test_related_records')->insertGetId([
        'parent_id' => 0,
        'category_id' => 0,
        'label' => 'Existing relation',
        'active' => true,
    ]);
    $columns = Schema::getColumnListing('test_crud_records');
    $foreignKeyColumn = array_search('test_related_records_id', $columns, true);
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Relation'],
            [
                ['Uses existing', 'Existing relation'],
                ['Creates relation', 'New relation'],
            ],
            [
                array_search('name', $columns, true) => 'name',
                $foreignKeyColumn => 'relation',
            ],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 2,
            'select_column' => [
                array_search('name', $columns, true) => 'name',
                $foreignKeyColumn => 'relation',
            ],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->orderBy('id')->pluck('test_related_records_id')->all())
            ->toBe([$existingId, $existingId + 1])
            ->and(DB::table('test_related_records')->where('label', 'New relation')->exists())->toBeTrue();
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('rolls back relation inserts when an imported row fails and continues later rows', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->unsignedBigInteger('test_related_records_id')->nullable();
        $table->unique('name');
    });
    DB::table('cms_moduls')->insert([
        'name' => 'Related Records',
        'path' => 'related-records',
        'table_name' => 'test_related_records',
        'controller' => 'TestRelatedRecordController',
    ]);

    $columns = Schema::getColumnListing('test_crud_records');
    $foreignKeyColumn = array_search('test_related_records_id', $columns, true);
    $temporaryStoragePath = null;

    try {
        [$temporaryStoragePath, $file] = prepareImportCsv(
            ['Name', 'Relation'],
            [
                ['Duplicate', 'First relation'],
                ['Duplicate', 'Must roll back'],
                ['After failure', 'Later relation'],
            ],
            [
                array_search('name', $columns, true) => 'name',
                $foreignKeyColumn => 'relation',
            ],
        );

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 3,
            'select_column' => [
                array_search('name', $columns, true) => 'name',
                $foreignKeyColumn => 'relation',
            ],
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query(['file' => $file]))
            ->assertOk()
            ->assertExactJson(['status' => true]);

        expect(DB::table('test_crud_records')->orderBy('id')->pluck('name')->all())
            ->toBe(['Duplicate', 'After failure'])
            ->and(DB::table('test_related_records')->where('label', 'First relation')->exists())->toBeTrue()
            ->and(DB::table('test_related_records')->where('label', 'Must roll back')->exists())->toBeFalse()
            ->and(DB::table('test_related_records')->where('label', 'Later relation')->exists())->toBeTrue();

        $this->withSession([
            'admin_id' => 1,
            'admin_is_superadmin' => true,
            'total_data_import' => 3,
        ])
            ->post(route('TestCrudControllerPostDoImportChunk').'?'.http_build_query([
                'file' => $file,
                'resume' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('progress', 100)
            ->assertJsonPath('last_error', fn (?string $error): bool => $error !== null && str_contains($error, 'Duplicate'));
    } finally {
        if ($temporaryStoragePath !== null) {
            File::deleteDirectory($temporaryStoragePath);
        }
    }
});

it('requires backend login before exporting data', function (): void {
    $this->post(route('TestCrudControllerPostExportData'), ['fileformat' => 'csv'])
        ->assertRedirect('/'.config('crudbooster.ADMIN_PATH').'/login');
});

it('exports filtered and limited data as PDF, XLSX, and CSV with legacy filenames and page-size persistence', function (): void {
    addSqliteExportSchemaMetadata();

    foreach (range(1, 205) as $number) {
        DB::table('test_crud_records')->insert([
            'name' => 'Filtered record '.$number,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    DB::table('test_crud_records')->insert([
        'name' => 'Excluded record',
        'status' => 'closed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('cms_settings')->insert([
        'name' => 'default_paper_size',
        'content' => 'Letter',
    ]);

    $exportInput = [
        'filename' => 'Filtered report',
        'limit' => 2,
        'columns' => ['name', 'status'],
        'filter_column' => [
            'name' => [
                'value' => 'Filtered record',
                'type' => 'like',
            ],
        ],
        'page_size' => 'A4',
        'page_orientation' => 'landscape',
        'default_paper_size' => '1',
    ];
    $session = [
        'admin_id' => 1,
        'admin_is_superadmin' => false,
        'admin_privileges_roles' => [
            (object) [
                'path' => 'crud-test',
                'is_visible' => true,
                'is_read' => true,
            ],
        ],
    ];

    $pdfResponse = $this->withSession($session)
        ->post(route('TestCrudControllerPostExportData'), [...$exportInput, 'fileformat' => 'pdf']);

    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-disposition'))
        ->toContain('inline')
        ->toContain('Filtered report.pdf')
        ->and($pdfResponse->getContent())->toStartWith('%PDF');
    expect(DB::table('cms_settings')->where('name', 'default_paper_size')->value('content'))->toBe('A4');

    $withoutSavingPaperSize = $exportInput;
    unset($withoutSavingPaperSize['default_paper_size']);
    $this->withSession($session)
        ->post(route('TestCrudControllerPostExportData'), [...$withoutSavingPaperSize, 'fileformat' => 'pdf'])
        ->assertOk();
    expect(DB::table('cms_settings')->where('name', 'default_paper_size')->value('content'))->toBe('A4');

    $xlsxResponse = $this->withSession($session)
        ->post(route('TestCrudControllerPostExportData'), [...$exportInput, 'fileformat' => 'xls']);

    $xlsxResponse->assertOk()->assertDownload('Filtered report.xlsx');
    $xlsxPath = $xlsxResponse->baseResponse->getFile()->getPathname();
    $xlsxRows = IOFactory::load($xlsxPath)->getActiveSheet()->toArray();
    $xlsxContent = implode("\n", array_map(static fn (array $row): string => implode('|', $row), $xlsxRows));
    expect($xlsxContent)
        ->toContain('Filtered record 205')
        ->toContain('Filtered record 204')
        ->not->toContain('Filtered record 203')
        ->not->toContain('Excluded record');

    $csvResponse = $this->withSession($session)
        ->post(route('TestCrudControllerPostExportData'), [...$exportInput, 'fileformat' => 'csv']);

    $csvResponse->assertOk()->assertDownload('Filtered report.csv');
    $csvPath = $csvResponse->baseResponse->getFile()->getPathname();
    $csvContent = file_get_contents($csvPath);
    expect($csvContent)
        ->toContain('Filtered record 205')
        ->toContain('Filtered record 204')
        ->not->toContain('Filtered record 203')
        ->not->toContain('Excluded record');

    expect(DB::table('cms_settings')->where('name', 'default_paper_size')->value('content'))->toBe('A4');
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

it('creates a parent record with pivot and child rows and preserves hook and redirect behavior', function (): void {
    DB::table('test_crud_categories')->insert([
        ['name' => 'First'],
        ['name' => 'Second'],
    ]);

    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $response = $this->from('/admin/crud-test/add')
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostAddSave'), [
            'name' => 'Parent record',
            'status' => 'active',
            'categories' => [1, 2],
            'notes-body' => ['First note', 'Second note'],
            'return_url' => '/admin/crud-test',
        ]);

    $response->assertRedirect('/admin/crud-test')
        ->assertSessionHas('message_type', 'success');

    $record = DB::table('test_crud_records')->where('name', 'Parent record')->first();

    expect($record)->not->toBeNull()
        ->and($record->status)->toBe('active')
        ->and($record->created_at)->not->toBeNull()
        ->and(DB::table('test_crud_record_category')->where('test_crud_records_id', $record->id)->pluck('test_crud_categories_id')->all())->toBe([1, 2])
        ->and(DB::table('test_crud_notes')->where('record_id', $record->id)->orderBy('id')->pluck('body')->all())->toBe(['First note', 'Second note'])
        ->and($controller->hookCalls)->toBe(['before_add', 'after_add']);
});

it('updates parent, pivot, and child rows without changing update hook ordering', function (): void {
    DB::table('test_crud_categories')->insert([
        ['name' => 'First'],
        ['name' => 'Second'],
    ]);
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Original record',
        'status' => 'draft',
        'created_at' => '2025-01-01 00:00:00',
        'updated_at' => '2025-01-01 00:00:00',
    ]);
    DB::table('test_crud_record_category')->insert([
        'test_crud_records_id' => $recordId,
        'test_crud_categories_id' => 1,
    ]);
    DB::table('test_crud_notes')->insert([
        'id' => 7,
        'record_id' => $recordId,
        'body' => 'Old note',
    ]);

    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $response = $this->from('/admin/crud-test/edit/'.$recordId)
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostEditSave', ['one' => $recordId]), [
            'name' => 'Updated record',
            'status' => 'published',
            'categories' => [2],
            'notes-body' => ['Replacement one', 'Replacement two'],
            'return_url' => '/admin/crud-test',
        ]);

    $response->assertRedirect('/admin/crud-test')
        ->assertSessionHas('message_type', 'success');

    expect(DB::table('test_crud_records')->where('id', $recordId)->value('name'))->toBe('Updated record')
        ->and(DB::table('test_crud_records')->where('id', $recordId)->value('updated_at'))->not->toBe('2025-01-01 00:00:00')
        ->and(DB::table('test_crud_record_category')->where('test_crud_records_id', $recordId)->pluck('test_crud_categories_id')->all())->toBe([2])
        ->and(DB::table('test_crud_notes')->where('record_id', $recordId)->orderBy('id')->pluck('body')->all())->toBe(['Replacement one', 'Replacement two'])
        ->and(DB::table('test_crud_notes')->where('record_id', $recordId)->orderBy('id')->pluck('id')->all())->toBe([1, 2])
        ->and($controller->hookCalls)->toBe(['before_edit', 'after_edit']);
});

it('deletes one record and preserves before/after delete hooks and the redirect', function (): void {
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Record to delete',
        'status' => 'active',
    ]);
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $response = $this->from('/admin/crud-test')
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostDelete', ['one' => $recordId]));

    $response->assertRedirect('/admin/crud-test')
        ->assertSessionHas('message_type', 'success');

    expect(DB::table('test_crud_records')->where('id', $recordId)->exists())->toBeFalse()
        ->and($controller->hookCalls)->toBe(['before_delete', 'after_delete']);
});

it('bulk deletes selected records and calls the delete hooks once with the selected IDs', function (): void {
    $recordIds = [
        DB::table('test_crud_records')->insertGetId(['name' => 'First record']),
        DB::table('test_crud_records')->insertGetId(['name' => 'Second record']),
    ];
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $response = $this->from('/admin/crud-test')
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostActionSelected'), [
            'checkbox' => $recordIds,
            'button_name' => 'delete',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('message_type', 'success');

    expect(DB::table('test_crud_records')->whereIn('id', $recordIds)->exists())->toBeFalse()
        ->and($controller->hookCalls)->toBe(['before_delete', 'after_delete']);
});

it('returns not found for missing update and delete IDs without changing records', function (): void {
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Untouched record',
        'status' => 'active',
    ]);

    $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostEditSave', ['one' => 999]), [
            'name' => 'Must not be created',
            'status' => 'changed',
        ])
        ->assertNotFound();

    $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostDelete', ['one' => 999]))
        ->assertNotFound();

    expect(DB::table('test_crud_records')->count())->toBe(1)
        ->and(DB::table('test_crud_records')->where('id', $recordId)->value('name'))->toBe('Untouched record')
        ->and(DB::table('test_crud_records')->where('id', $recordId)->value('status'))->toBe('active');
});

it('preserves UUID primary keys and passes the configured key to the after-add hook', function (): void {
    $controller = new TestCrudUuidController;
    app()->instance(TestCrudUuidController::class, $controller);

    $response = $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudUuidControllerPostAddSave'), [
            'id' => 'record-uuid-1',
            'name' => 'UUID record',
            'return_url' => '/admin/crud-uuid-test',
        ]);

    $response->assertRedirect('/admin/crud-uuid-test')
        ->assertSessionHas('message_type', 'success');

    expect(DB::table('test_crud_uuid_records')->where('id', 'record-uuid-1')->value('name'))->toBe('UUID record')
        ->and($controller->lastAfterAddId)->toBe('record-uuid-1')
        ->and($controller->hookCalls)->toBe(['before_add', 'after_add']);
});

it('rolls back all create writes when a pivot insert fails', function (): void {
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $this->withoutExceptionHandling();

    expect(fn () => $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostAddSave'), [
            'name' => 'Parent record',
            'status' => 'active',
            'categories' => [999],
            'notes-body' => ['Must roll back'],
        ]))->toThrow(QueryException::class);

    expect(DB::table('test_crud_records')->count())->toBe(0)
        ->and(DB::table('test_crud_record_category')->count())->toBe(0)
        ->and(DB::table('test_crud_notes')->count())->toBe(0)
        ->and($controller->hookCalls)->toBe(['before_add']);
});

it('rolls back parent and pivot writes when child-row insertion fails', function (): void {
    DB::table('test_crud_categories')->insert(['name' => 'Valid category']);
    DB::unprepared("CREATE TRIGGER reject_test_crud_notes BEFORE INSERT ON test_crud_notes BEGIN SELECT RAISE(ABORT, 'notes rejected'); END");
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $this->withoutExceptionHandling();

    expect(fn () => $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostAddSave'), [
            'name' => 'Parent record',
            'status' => 'active',
            'categories' => [1],
            'notes-body' => ['Rejected note'],
        ]))->toThrow(QueryException::class);

    expect(DB::table('test_crud_records')->count())->toBe(0)
        ->and(DB::table('test_crud_record_category')->count())->toBe(0)
        ->and(DB::table('test_crud_notes')->count())->toBe(0)
        ->and($controller->hookCalls)->toBe(['before_add']);
});

it('rolls back create when the configured child table does not have the requested column', function (): void {
    Schema::drop('test_crud_notes');
    Schema::create('test_crud_notes', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('record_id');
        $table->string('other_body');
    });

    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $this->withoutExceptionHandling();

    expect(fn () => $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostAddSave'), [
            'name' => 'Parent record',
            'notes-body' => ['Invalid child column'],
        ]))->toThrow(QueryException::class);

    expect(DB::table('test_crud_records')->count())->toBe(0)
        ->and(DB::table('test_crud_record_category')->count())->toBe(0)
        ->and($controller->hookCalls)->toBe(['before_add']);
});

it('rolls back parent, pivot, and child changes when an update relation fails', function (): void {
    DB::table('test_crud_categories')->insert(['name' => 'Existing category']);
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Original record',
        'status' => 'draft',
    ]);
    DB::table('test_crud_record_category')->insert([
        'test_crud_records_id' => $recordId,
        'test_crud_categories_id' => 1,
    ]);
    DB::table('test_crud_notes')->insert([
        'record_id' => $recordId,
        'body' => 'Original note',
    ]);

    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $this->withoutExceptionHandling();

    expect(fn () => $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostEditSave', ['one' => $recordId]), [
            'name' => 'Changed record',
            'status' => 'published',
            'categories' => [999],
            'notes-body' => ['Changed note'],
        ]))->toThrow(QueryException::class);

    expect(DB::table('test_crud_records')->where('id', $recordId)->value('name'))->toBe('Original record')
        ->and(DB::table('test_crud_record_category')->where('test_crud_records_id', $recordId)->pluck('test_crud_categories_id')->all())->toBe([1])
        ->and(DB::table('test_crud_notes')->where('record_id', $recordId)->pluck('body')->all())->toBe(['Original note'])
        ->and($controller->hookCalls)->toBe(['before_edit']);
});

it('restores existing child rows and parent data when a child replacement fails', function (): void {
    DB::table('test_crud_categories')->insert(['name' => 'Existing category']);
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Original record',
        'status' => 'draft',
    ]);
    DB::table('test_crud_record_category')->insert([
        'test_crud_records_id' => $recordId,
        'test_crud_categories_id' => 1,
    ]);
    DB::table('test_crud_notes')->insert([
        'record_id' => $recordId,
        'body' => 'Original note',
    ]);
    DB::unprepared("CREATE TRIGGER reject_test_crud_notes BEFORE INSERT ON test_crud_notes BEGIN SELECT RAISE(ABORT, 'notes rejected'); END");

    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $this->withoutExceptionHandling();

    expect(fn () => $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostEditSave', ['one' => $recordId]), [
            'name' => 'Changed record',
            'status' => 'published',
            'categories' => [1],
            'notes-body' => ['Replacement note'],
        ]))->toThrow(QueryException::class);

    expect(DB::table('test_crud_records')->where('id', $recordId)->value('name'))->toBe('Original record')
        ->and(DB::table('test_crud_record_category')->where('test_crud_records_id', $recordId)->pluck('test_crud_categories_id')->all())->toBe([1])
        ->and(DB::table('test_crud_notes')->where('record_id', $recordId)->pluck('body')->all())->toBe(['Original note'])
        ->and($controller->hookCalls)->toBe(['before_edit']);
});

it('does not bulk-delete any rows when one selected ID is invalid', function (): void {
    $recordId = DB::table('test_crud_records')->insertGetId(['name' => 'Keep this record']);
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostActionSelected'), [
            'checkbox' => [$recordId, 999],
            'button_name' => 'delete',
        ])
        ->assertNotFound();

    expect(DB::table('test_crud_records')->where('id', $recordId)->exists())->toBeTrue()
        ->and($controller->hookCalls)->toBe([]);
});

it('dispatches custom bulk actions with the original selected IDs and button name', function (): void {
    $recordIds = [
        DB::table('test_crud_records')->insertGetId(['name' => 'First selected']),
        DB::table('test_crud_records')->insertGetId(['name' => 'Second selected']),
    ];
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $this->from('/admin/crud-test')
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostActionSelected'), [
            'checkbox' => $recordIds,
            'button_name' => 'mark-reviewed',
        ])
        ->assertRedirect()
        ->assertSessionHas('message_type', 'success');

    expect($controller->selectedActionCalls)->toBe([[$recordIds, 'mark-reviewed']])
        ->and(DB::table('test_crud_records')->whereIn('id', $recordIds)->count())->toBe(2);
});

it('clears pivot and child rows when an update omits their inputs', function (): void {
    DB::table('test_crud_categories')->insert(['name' => 'Existing category']);
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Record with relations',
        'status' => 'active',
    ]);
    DB::table('test_crud_record_category')->insert([
        'test_crud_records_id' => $recordId,
        'test_crud_categories_id' => 1,
    ]);
    DB::table('test_crud_notes')->insert([
        'record_id' => $recordId,
        'body' => 'Existing note',
    ]);

    $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostEditSave', ['one' => $recordId]), [
            'name' => 'Record with cleared relations',
            'status' => 'active',
        ])
        ->assertRedirect();

    expect(DB::table('test_crud_record_category')->where('test_crud_records_id', $recordId)->count())->toBe(0)
        ->and(DB::table('test_crud_notes')->where('record_id', $recordId)->count())->toBe(0);
});

it('soft-deletes records when the configured table has a deleted-at column', function (): void {
    Schema::table('test_crud_records', function (Blueprint $table): void {
        $table->timestamp('deleted_at')->nullable();
    });
    $recordId = DB::table('test_crud_records')->insertGetId(['name' => 'Soft delete record']);

    $this->from('/admin/crud-test')
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostDelete', ['one' => $recordId]))
        ->assertRedirect('/admin/crud-test')
        ->assertSessionHas('message_type', 'success');

    expect(DB::table('test_crud_records')->where('id', $recordId)->exists())->toBeTrue()
        ->and(DB::table('test_crud_records')->where('id', $recordId)->value('deleted_at'))->not->toBeNull();
});

it('does not persist or invoke hooks when create validation fails', function (): void {
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    foreach ([['status' => 'active'], ['name' => '', 'status' => 'active']] as $input) {
        $this->from('/admin/crud-test/add')
            ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
            ->post(route('TestCrudControllerPostAddSave'), $input)
            ->assertRedirect()
            ->assertSessionHas('message_type', 'warning');
    }

    expect(DB::table('test_crud_records')->count())->toBe(0)
        ->and($controller->hookCalls)->toBe([]);
});

it('does not persist or invoke hooks when create permission is denied', function (): void {
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);

    $this->withSession([
        'admin_id' => 1,
        'admin_is_superadmin' => false,
        'admin_privileges_roles' => [],
    ])->post(route('TestCrudControllerPostAddSave'), [
        'name' => 'Denied record',
        'status' => 'active',
    ])->assertRedirect('/admin');

    expect(DB::table('test_crud_records')->count())->toBe(0)
        ->and($controller->hookCalls)->toBe([]);
});

it('does not update or delete records when write permissions are denied', function (): void {
    $recordId = DB::table('test_crud_records')->insertGetId([
        'name' => 'Protected record',
        'status' => 'original',
    ]);
    $controller = new TestCrudController;
    app()->instance(TestCrudController::class, $controller);
    $session = [
        'admin_id' => 1,
        'admin_is_superadmin' => false,
        'admin_privileges_roles' => [],
    ];

    $this->withSession($session)
        ->post(route('TestCrudControllerPostEditSave', ['one' => $recordId]), [
            'name' => 'Unauthorized update',
            'status' => 'changed',
        ])
        ->assertRedirect('/admin');

    $this->withSession($session)
        ->post(route('TestCrudControllerPostDelete', ['one' => $recordId]))
        ->assertRedirect('/admin');

    expect(DB::table('test_crud_records')->where('id', $recordId)->value('name'))->toBe('Protected record')
        ->and(DB::table('test_crud_records')->where('id', $recordId)->value('status'))->toBe('original')
        ->and(DB::table('test_crud_records')->where('id', $recordId)->exists())->toBeTrue()
        ->and($controller->hookCalls)->toBe([]);
});

it('writes the CRUD log after a successful create when debug logging is enabled', function (): void {
    DB::table('cms_settings')->insert([
        'name' => 'api_debug_mode',
        'content' => '1',
    ]);
    $server = $_SERVER;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'CRUD characterization test';

    try {
        $this->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
            ->post(route('TestCrudControllerPostAddSave'), [
                'name' => 'Logged record',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('message_type', 'success');
    } finally {
        $_SERVER = $server;
    }

    expect(DB::table('cms_logs')->count())->toBe(1)
        ->and(DB::table('cms_logs')->value('description'))->toBe(cbLang('log_add', [
            'name' => 'Logged record',
            'module' => 'CRUD Test',
        ]));
});

it('returns the legacy JSON redirect payload for AJAX CRUD writes', function (): void {
    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->withSession(['admin_id' => 1, 'admin_is_superadmin' => true])
        ->post(route('TestCrudControllerPostAddSave'), [
            'name' => 'AJAX record',
            'status' => 'active',
            'return_url' => '/admin/crud-test',
        ]);

    $response->assertOk()
        ->assertJson([
            'message_type' => 'success',
            'redirect_url' => '/admin/crud-test',
        ]);
});

<?php

use App\Http\Controllers\CRUDBooster\TestCrudController;
use crocodicstudio\crudbooster\commands\CrudboosterInstallationCommand;
use crocodicstudio\crudbooster\commands\CrudboosterUpdateCommand;
use crocodicstudio\crudbooster\commands\CrudboosterVersionCommand;
use crocodicstudio\crudbooster\commands\Mailqueues;
use crocodicstudio\crudbooster\controllers\CBController;
use crocodicstudio\crudbooster\CRUDBoosterServiceProvider;
use crocodicstudio\crudbooster\helpers\CB;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

it('locks package static routes, verbs, names, and middleware', function (): void {
    $expected = [
        ['getLogin', 'GET', 'admin/login', ['web']],
        ['postLogin', 'POST', 'admin/login', ['web']],
        ['getLogout', 'GET', 'admin/logout', ['web']],
        ['getForgot', 'GET', 'admin/forgot', ['web']],
        ['postForgot', 'POST', 'admin/forgot', ['web']],
        ['getLockScreen', 'GET', 'admin/lock-screen', ['web']],
        ['postUnlockScreen', 'POST', 'admin/unlock-screen', ['web']],
        ['getRegister', 'GET', 'admin/register', ['web']],
        ['postRegister', 'POST', 'admin/register', ['web']],
        ['apiDocumentation', 'GET', 'api-documentation', ['web']],
        ['downloadDocumentationPostman', 'GET', 'download-documentation-postman', ['web']],
        ['fileControllerPreview', 'GET', 'uploads/{one?}/{two?}/{three?}/{four?}/{five?}', ['web']],
        ['ApiCustomControllerGetIndex', 'GET', 'admin/api_generator', ['web', '\crocodicstudio\crudbooster\middlewares\CBBackend']],
        ['ApiCustomControllerPostDelete', 'POST', 'admin/api_generator/delete/{one?}/{two?}/{three?}/{four?}/{five?}', ['web', '\crocodicstudio\crudbooster\middlewares\CBBackend']],
    ];

    foreach ($expected as [$name, $verb, $uri, $middleware]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($verb)
            ->and($route->uri())->toBe($uri);

        foreach ($middleware as $expectedMiddleware) {
            expect($route->gatherMiddleware())->toContain($expectedMiddleware);
        }
    }

    $tokenRoute = collect(Route::getRoutes()->getRoutes())->first(
        fn (RoutingRoute $route): bool => $route->uri() === 'api/get-token'
    );

    expect($tokenRoute)->not->toBeNull()
        ->and($tokenRoute->methods())->toContain('POST')
        ->and($tokenRoute->gatherMiddleware())->toContain('api');
});

it('locks config keys, publishing tags, aliases, commands, and container bindings', function (): void {
    $configKeys = array_keys(config('crudbooster'));

    expect($configKeys)->toEqualCanonicalizing([
        'ADMIN_PATH',
        'API_USER_AGENT_ALLOWED',
        'USER_TABLE',
        'IMAGE_FIELDS_CANDIDATE',
        'PASSWORD_FIELDS_CANDIDATE',
        'DATE_FIELDS_CANDIDATE',
        'EMAIL_FIELDS_CANDIDATE',
        'PHONE_FIELDS_CANDIDATE',
        'NAME_FIELDS_CANDIDATE',
        'URL_FIELDS_CANDIDATE',
        'UPLOAD_TYPES',
        'DEFAULT_THUMBNAIL_WIDTH',
        'DEFAULT_UPLOAD_MAX_SIZE',
        'IMAGE_EXTENSIONS',
        'MAIN_DB_DATABASE',
        'MULTIPLE_DATABASE_MODULE',
        'ADMIN_LAYOUT',
        'styles',
        'scripts',
        'filesystem_driver',
        'prefix_upload',
    ])
        ->and(config('crudbooster.ADMIN_PATH'))->toBe('admin')
        ->and(config('crudbooster.USER_TABLE'))->toBe('cms_users')
        ->and(app('crudbooster'))->toBeInstanceOf(CRUDBooster::class)
        ->and(class_exists(CB::class))->toBeTrue();

    $provider = CRUDBoosterServiceProvider::class;

    expect(ServiceProvider::pathsToPublish($provider, 'cb-config'))->not->toBeEmpty()
        ->and(ServiceProvider::pathsToPublish($provider, 'cb-controllers'))->not->toBeEmpty()
        ->and(ServiceProvider::pathsToPublish($provider, 'cb-assets'))->not->toBeEmpty()
        ->and(ServiceProvider::pathsToPublish($provider, 'cb-seeders'))->not->toBeEmpty();

    $commands = collect(Artisan::all());

    expect($commands->has('crudbooster:install'))->toBeTrue()
        ->and($commands->has('crudbooster:update'))->toBeTrue()
        ->and($commands->has('crudbooster:version'))->toBeTrue()
        ->and($commands->has('mailqueues'))->toBeTrue()
        ->and($commands->get('crudbooster:install'))->toBeInstanceOf(CrudboosterInstallationCommand::class)
        ->and($commands->get('crudbooster:update'))->toBeInstanceOf(CrudboosterUpdateCommand::class)
        ->and($commands->get('crudbooster:version'))->toBeInstanceOf(CrudboosterVersionCommand::class)
        ->and($commands->get('mailqueues'))->toBeInstanceOf(Mailqueues::class);
});

it('locks helper functions and public CRUDBooster helper methods used by applications', function (): void {
    foreach ([
        'ends_with',
        'cbLang',
        'db',
        'assetThumbnail',
        'assetResize',
        'extract_unit',
        'now',
        'get_setting',
        'str_random',
        'str_slug',
        'str_limit',
        'g',
        'min_var_export',
        'rrmdir',
    ] as $function) {
        expect(function_exists($function))->toBeTrue();
    }

    $publicMethods = collect((new ReflectionClass(CRUDBooster::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => $method->class === CRUDBooster::class)
        ->map(fn (ReflectionMethod $method): string => $method->name)
        ->values();

    expect($publicMethods)->toContain(
        'echoSelect2Mult',
        'uploadBase64',
        'uploadFile',
        'uploadRawFile',
        'getSetting',
        'insert',
        'first',
        'get',
        'me',
        'myId',
        'isSuperadmin',
        'myName',
        'myPhoto',
        'myPrivilege',
        'myPrivilegeId',
        'myPrivilegeName',
        'isLocked',
        'redirectBack',
        'redirect',
        'isView',
        'isUpdate',
        'isCreate',
        'isRead',
        'isDelete',
        'isCRUD',
        'getCurrentModule',
        'getCurrentDashboardId',
        'getCurrentMenuId',
        'sidebarDashboard',
        'sidebarMenu',
        'deleteConfirm',
        'getModulePath',
        'mainpath',
        'adminPath',
        'getCurrentId',
        'getCurrentMethod',
        'clearCache',
        'isColumnNULL',
        'getFieldType',
        'getValueFilter',
        'getSortingFilter',
        'getTypeFilter',
        'stringBetween',
        'timeAgo',
        'sendEmailQueue',
        'sendEmail',
        'valid',
        'parseSqlTable',
        'putCache',
        'getCache',
        'flushCache',
        'forgetCache',
        'pk',
        'findPrimaryKey',
        'newId',
        'isColumnExists',
        'getForeignKey',
        'getTableForeignKey',
        'isForeignKey',
        'urlFilterColumn',
        'insertLog',
        'referer',
        'listTables',
        'getUrlParameters',
        'authAPI',
        'sendNotification',
        'sendFCM',
        'getTableColumns',
        'getNameTable',
        'isExistsController',
        'generateControllerName',
        'generateAPI',
        'generateController',
        'routeController',
    )
        ->and(method_exists(CB::class, 'ES2M'))->toBeTrue();
});

it('locks CBController extension points as public configuration, hooks, and HTTP adapters', function (): void {
    $reflection = new ReflectionClass(CBController::class);
    $publicProperties = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
        ->map(fn (ReflectionProperty $property): string => $property->name)
        ->all();
    $publicMethods = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => $method->class === CBController::class)
        ->map(fn (ReflectionMethod $method): string => $method->name)
        ->all();

    expect($publicProperties)->toContain(
        'table',
        'primary_key',
        'title_field',
        'col',
        'form',
        'global_privilege',
        'button_add',
        'button_edit',
        'button_delete',
        'button_detail',
        'button_import',
        'button_export',
        'hide_form',
        'addaction',
        'button_selected',
    )
        ->and($publicMethods)->toContain(
            'cbLoader',
            'getIndex',
            'getAdd',
            'postAddSave',
            'getEdit',
            'postEditSave',
            'postDelete',
            'getDetail',
            'getImportData',
            'postExportData',
            'getDataTable',
            'getModalData',
            'postUploadFile',
            'hook_before_add',
            'hook_after_add',
            'hook_before_edit',
            'hook_after_edit',
            'hook_before_delete',
            'hook_after_delete',
            'hook_query_index',
            'hook_row_index',
            'actionButtonSelected',
            'validation',
            'input_assignment',
        )
        ->and($reflection->hasMethod('cbInit'))->toBeFalse()
        ->and(method_exists(TestCrudController::class, 'cbInit'))->toBeTrue();
});

it('resolves package views through the crudbooster namespace and documents component override paths', function (): void {
    $hints = View::getFinder()->getHints()['crudbooster'] ?? [];
    $packageViews = realpath(__DIR__.'/../../src/views');
    $formBody = File::get($packageViews.'/default/form_body.blade.php');

    $resolvedHints = collect($hints)->map(fn (string $path): string|false => realpath($path))->filter()->all();

    expect($resolvedHints)->toContain($packageViews)
        ->and(View::exists('crudbooster::login'))->toBeTrue()
        ->and(View::exists('crudbooster::default.form'))->toBeTrue()
        ->and(View::exists('crudbooster::default.index'))->toBeTrue()
        ->and(View::exists('crudbooster::default.type_components.text.component'))->toBeTrue()
        ->and(View::exists('crudbooster::admin_template'))->toBeTrue()
        ->and($formBody)->toContain("resource_path('views/vendor/crudbooster/type_components/'")
        ->and($formBody)->toContain("base_path('/vendor/wyyr/crudbooster/src/views/default/type_components/'");

    $types = collect(File::directories($packageViews.'/default/type_components'))
        ->map(fn (string $path): string => basename($path))
        ->sort()
        ->values()
        ->all();

    expect($types)->toEqual([
        'checkbox',
        'child',
        'custom',
        'datamodal',
        'date',
        'datetime',
        'email',
        'googlemaps',
        'header',
        'hidden',
        'json',
        'money',
        'multitext',
        'number',
        'password',
        'radio',
        'select',
        'select2',
        'text',
        'textarea',
        'time',
        'upload',
        'wysiwyg',
    ]);
});

it('does not register Laravel events or listeners from the package provider', function (): void {
    $provider = file_get_contents((new ReflectionClass(CRUDBoosterServiceProvider::class))->getFileName());

    expect($provider)->not->toContain('Event::')
        ->and($provider)->not->toContain('listen(');
});

it('loads migrations from the package database path', function (): void {
    $migrationPath = realpath(__DIR__.'/../../src/database/migrations');
    $files = collect(File::files($migrationPath))->map(fn ($file) => $file->getFilename())->all();

    expect($files)->toContain('2016_08_07_152421_add_table_cms_users.php')
        ->and($files)->toContain('2016_08_07_154624_add_table_cms_moduls.php')
        ->and($files)->toContain('2016_08_07_152014_add_table_cms_privileges.php')
        ->and(count($files))->toBe(25);
});

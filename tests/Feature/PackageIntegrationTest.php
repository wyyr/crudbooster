<?php

use App\Http\Controllers\CRUDBooster\TestRelationController;
use crocodicstudio\crudbooster\controllers\ApiCustomController;
use crocodicstudio\crudbooster\CRUDBoosterServiceProvider;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\Fixtures;
use Tests\Fixtures\ExplicitSeeder;
use Tests\Fixtures\TestCsrfMiddleware;
use Tests\Fixtures\TestRouteController;

beforeEach(function (): void {
    Route::get('/test-notification-latest', fn () => '')->name('NotificationControllerGetLatestJson');
    Route::get('/test-notification-index', fn () => '')->name('NotificationControllerGetIndex');

    Route::group([
        'prefix' => config('crudbooster.ADMIN_PATH'),
        'namespace' => 'App\Http\Controllers\CRUDBooster',
    ], function (): void {
        CRUDBooster::routeController('relation-test', class_basename(TestRelationController::class));
    });
});

it('boots the provider and registers package routes', function (): void {
    expect(app()->providerIsLoaded(CRUDBoosterServiceProvider::class))->toBeTrue()
        ->and(app('crudbooster'))->toBeInstanceOf(CRUDBooster::class)
        ->and(Route::has('getLogin'))->toBeTrue();
});

it('does not register raw SQL or unrestricted record update endpoints', function (): void {
    expect(Route::has('ApiCustomControllerPostDataQuery'))->toBeFalse()
        ->and(Route::has('ApiCustomControllerGetUpdateSingle'))->toBeFalse()
        ->and(Route::has('AdminControllerGetUpdateSingle'))->toBeFalse();
});

it('registers destructive actions only as POST routes', function (): void {
    Route::group(['namespace' => 'crocodicstudio\crudbooster\controllers'], function (): void {
        foreach ([
            'PrivilegeController',
            'SettingController',
            'StatisticBuilderController',
            'NotificationController',
        ] as $controller) {
            CRUDBooster::routeController('security-test/'.$controller, $controller, 'crocodicstudio\crudbooster\controllers');
        }
    });

    foreach ([
        'TestRelationControllerPostDelete',
        'TestRelationControllerPostDeleteImage',
        'PrivilegeControllerPostDelete',
        'SettingControllerPostDeleteFileSetting',
        'StatisticBuilderControllerPostDeleteComponent',
        'NotificationControllerPostRead',
    ] as $routeName) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain('POST');
    }

    $deleteRoute = Route::getRoutes()->getByName('TestRelationControllerPostDelete');

    expect(Route::has('TestRelationControllerGetDelete'))->toBeFalse()
        ->and(Route::has('TestRelationControllerGetDeleteImage'))->toBeFalse()
        ->and(Route::has('TestRelationControllerGetDeleteLegacy'))->toBeFalse()
        ->and(Route::has('SettingControllerGetDeleteFileSetting'))->toBeFalse()
        ->and(Route::has('StatisticBuilderControllerGetDeleteComponent'))->toBeFalse()
        ->and(Route::has('NotificationControllerGetRead'))->toBeFalse()
        ->and($deleteRoute->uri())->toContain('relation-test/delete/');

    $this->get(route('TestRelationControllerPostDelete', ['one' => 1]))
        ->assertMethodNotAllowed();
});

it('does not let users mark another user notification as read', function (): void {
    Schema::create('cms_notifications', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('id_cms_users')->nullable();
        $table->string('content')->nullable();
        $table->string('url')->nullable();
        $table->boolean('is_read')->nullable();
        $table->timestamps();
    });

    Route::group(['namespace' => 'crocodicstudio\crudbooster\controllers'], function (): void {
        CRUDBooster::routeController('security-test/notifications', 'NotificationController', 'crocodicstudio\crudbooster\controllers');
    });

    $notificationId = DB::table('cms_notifications')->insertGetId([
        'id_cms_users' => 999,
        'content' => 'Other user notification',
        'url' => '/',
        'is_read' => false,
    ]);

    $this->withoutMiddleware()
        ->withSession(['admin_id' => 1])
        ->post(route('NotificationControllerPostRead', ['one' => $notificationId]))
        ->assertNotFound();

    expect(DB::table('cms_notifications')->where('id', $notificationId)->value('is_read'))->toBe(0);
});

it('renders notification read actions with a CSRF-protected POST form', function (): void {
    $controller = app(\crocodicstudio\crudbooster\controllers\NotificationController::class);
    $controller->cbInit();
    $row = (object) ['id' => 7, 'content' => '<script>alert(1)</script>'];
    $callback = $controller->col[0]['callback_php'];

    eval('$value = '.$callback.';');

    expect($value)->toContain('method="POST"')
        ->toContain('name="_token"')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('submits CRUD delete confirmations as POST requests', function (): void {
    ob_start();
    CRUDBooster::deleteConfirm('/admin/relation-test/delete/1');
    $script = str_replace('\\/', '/', ob_get_clean());

    expect($script)->toContain('cbSubmitPostForm("/admin/relation-test/delete/1")')
        ->not->toContain('window.location.href');
});

it('restricts API key management and API exports to superadmins', function (): void {
    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => false])
        ->get(route('ApiCustomControllerGetScreetKey'))
        ->assertForbidden();

    $this->get(route('ApiCustomControllerGetDownloadPostman'))
        ->assertForbidden();

    foreach ([
        ['ApiCustomControllerGetGenerator', []],
        ['ApiCustomControllerGetEditApi', ['one' => 1]],
        ['ApiCustomControllerGetColumnTable', ['one' => 'cms_users']],
    ] as [$routeName, $parameters]) {
        $this->get(route($routeName, $parameters))->assertForbidden();
    }

    $this->post(route('ApiCustomControllerPostSaveApiCustom'))
        ->assertForbidden();

    foreach ([
        ['ApiCustomControllerPostGenerateScreetKey', []],
        ['ApiCustomControllerPostStatusApikey', []],
        ['ApiCustomControllerPostDeleteApiKey', []],
        ['ApiCustomControllerPostDeleteApi', ['one' => 1]],
    ] as [$routeName, $parameters]) {
        $this->post(route($routeName, $parameters))->assertForbidden();
    }
});

it('provides empty defaults for a new API generator form', function (): void {
    Schema::create('cms_apicustom', function (Blueprint $table): void {
        $table->id();
    });

    $action = ApiCustomController::class.'@getGenerator';
    $route = $this->createMock(RoutingRoute::class);
    $route->method('getActionName')->willReturn($action);
    $router = $this->createMock(Router::class);
    $router->method('getCurrentRoute')->willReturn($route);
    $router->method('currentRouteAction')->willReturn($action);
    Route::swap($router);
    Session::put('admin_is_superadmin', true);

    $response = app(ApiCustomController::class)->getGenerator();
    $viewData = $response->getData();

    expect($response)->toBeInstanceOf(View::class)
        ->and((array) $viewData['row'])->toMatchArray([
            'id' => null,
            'nama' => null,
            'tabel' => null,
            'permalink' => null,
            'aksi' => null,
            'method_type' => null,
            'keterangan' => null,
        ])
        ->and($viewData['parameters'])->toBeNull()
        ->and($viewData['responses'])->toBeNull();
});

it('changes API key status only through a validated POST request', function (): void {
    DB::table('cms_apikey')->insert([
        'screetkey' => 'test-secret',
        'hit' => 0,
        'status' => 'non active',
    ]);

    $statusRoute = Route::getRoutes()->getByName('ApiCustomControllerPostStatusApikey');

    expect($statusRoute)->not->toBeNull()
        ->and($statusRoute->methods())->toContain('POST')
        ->and(Route::has('ApiCustomControllerGetStatusApikey'))->toBeFalse()
        ->and(Route::has('ApiCustomControllerGetGenerateScreetKey'))->toBeFalse()
        ->and(Route::has('ApiCustomControllerGetDeleteApiKey'))->toBeFalse()
        ->and(Route::has('ApiCustomControllerGetDeleteApi'))->toBeFalse();

    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => true])
        ->get(route('ApiCustomControllerPostStatusApikey'))
        ->assertMethodNotAllowed();

    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => true])
        ->post(route('ApiCustomControllerPostStatusApikey'), [
            'id' => 1,
            'status' => '1',
        ])
        ->assertRedirect();

    expect(DB::table('cms_apikey')->where('id', 1)->value('status'))->toBe('active');
});

it('enforces CSRF tokens using Laravel web middleware', function (): void {
    Config::set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    app()->forgetInstance('encrypter');

    $session = app('session')->driver();
    $session->start();
    $token = $session->token();
    $middleware = app(TestCsrfMiddleware::class);
    $next = fn () => response('', 204);

    $missingTokenRequest = Request::create('/csrf-regression', 'POST');
    $missingTokenRequest->setLaravelSession($session);

    expect(fn () => $middleware->handle($missingTokenRequest, $next))
        ->toThrow(TokenMismatchException::class);

    $validTokenRequest = Request::create('/csrf-regression', 'POST', ['_token' => $token]);
    $validTokenRequest->setLaravelSession($session);

    expect($middleware->handle($validTokenRequest, $next)->getStatusCode())->toBe(204);
});

it('returns only configured relation data and ignores client SQL conditions', function (): void {
    DB::table('test_related_records')->insert([
        ['parent_id' => 5, 'category_id' => 9, 'label' => 'Configured result', 'active' => true],
        ['parent_id' => 5, 'category_id' => 9, 'label' => 'Inactive result', 'active' => false],
        ['parent_id' => 6, 'category_id' => 9, 'label' => 'Other parent', 'active' => true],
    ]);

    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => true])
        ->get(route('TestRelationControllerGetDataTable').'?'.http_build_query([
            'table' => 'test_related_records',
            'label' => 'label',
            'fk_name' => 'parent_id',
            'fk_value' => 5,
            'add_field_value' => 9,
            'datatable_where' => '1 = 0',
        ]))
        ->assertOk()
        ->assertExactJson([
            ['select_value' => 1, 'select_label' => 'Configured result'],
        ]);
});

it('rejects relation data tables that are not configured by the controller', function (): void {
    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => true])
        ->get(route('TestRelationControllerGetDataTable').'?'.http_build_query([
            'table' => 'cms_users',
            'label' => 'email',
            'fk_name' => 'id',
            'fk_value' => 1,
        ]))
        ->assertNotFound();
});

it('uses configured datamodal columns, filters and display metadata', function (): void {
    DB::table('test_related_records')->insert([
        'parent_id' => 5,
        'category_id' => 9,
        'label' => '<script>alert(1)</script>Configured result',
        'active' => true,
    ]);

    $response = $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => true])
        ->get(route('TestRelationControllerGetModalData').'?'.http_build_query([
            'table' => 'test_related_records',
            'columns' => 'id,label,category_id',
            'name_column' => 'childrenrelated_record_modal_id',
            'where' => '1 = 0',
            'select_to' => 'id:attacker_field',
            'columns_name_alias' => 'Attacker',
            'paginate' => 100000,
        ]))
        ->assertOk();

    expect($response->getContent())
        ->toContain('Label')
        ->toContain('alert(1)Configured result')
        ->not->toContain('<script>alert(1)</script>')
        ->not->toContain('Attacker');
});

it('denies relation data access to non-superadmins without module permissions', function (): void {
    $this->withoutMiddleware()
        ->withSession(['admin_is_superadmin' => false])
        ->get(route('TestRelationControllerGetModalData').'?'.http_build_query([
            'table' => 'test_related_records',
            'columns' => 'id,label,category_id',
            'name_column' => 'childrenrelated_record_modal_id',
        ]))
        ->assertForbidden();
});

it('allows relation data access with module read permission', function (): void {
    DB::table('test_related_records')->insert([
        'parent_id' => 5,
        'category_id' => 9,
        'label' => 'Permitted result',
        'active' => true,
    ]);

    $this->withoutMiddleware()
        ->withSession([
            'admin_is_superadmin' => false,
            'admin_privileges_roles' => [
                (object) [
                    'path' => 'relation-test',
                    'is_read' => true,
                    'is_create' => false,
                    'is_edit' => false,
                ],
            ],
        ])
        ->get(route('TestRelationControllerGetDataTable').'?'.http_build_query([
            'table' => 'test_related_records',
            'label' => 'label',
            'fk_name' => 'parent_id',
            'fk_value' => 5,
        ]))
        ->assertOk()
        ->assertJsonFragment(['select_label' => 'Permitted result']);
});

it('does not seed a default admin during provider boot', function (): void {
    expect(DB::table('cms_users')->where('email', 'admin@crudbooster.com')->exists())->toBeFalse();
});

it('publishes the package seeder through its explicit publishing tag', function (): void {
    $providerPath = (new ReflectionClass(CRUDBoosterServiceProvider::class))->getFileName();
    $sourceSeeder = dirname($providerPath).'/database/seeders/CBSeeder.php';
    $publishableSeeders = ServiceProvider::pathsToPublish(CRUDBoosterServiceProvider::class, 'cb-seeders');

    expect($publishableSeeders)->toHaveKey($sourceSeeder);
});

it('renders package views with asset urls matching the public publish path', function (): void {
    $providerPath = (new ReflectionClass(CRUDBoosterServiceProvider::class))->getFileName();
    $publishableAssets = ServiceProvider::pathsToPublish(CRUDBoosterServiceProvider::class, 'cb-assets');
    $sourcePublicPath = dirname($providerPath).'/public';

    Route::get('/test-notification-latest', fn () => '')->name('NotificationControllerGetLatestJson');
    Route::get('/test-notification-index', fn () => '')->name('NotificationControllerGetIndex');

    $errorPage = view('crudbooster::errors.404')->render();
    $dataModal = view('crudbooster::default.type_components.datamodal.browser', [
        'result' => new LengthAwarePaginator([], 0, 10),
        'columns' => [],
    ])->render();

    expect($publishableAssets[$sourcePublicPath] ?? null)->toBe(public_path())
        ->and($errorPage)->toContain(asset('crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css'))
        ->and($dataModal)->toContain(asset('crudbooster/ionic/css/ionicons.min.css'))
        ->and($errorPage.$dataModal)->not->toContain(asset('vendor/crudbooster/'));
});

it('compiles relation and POST delete views', function (): void {
    $blade = app('blade.compiler');
    $finder = app('view')->getFinder();

    foreach ([
        'crudbooster::default.type_components.select.component',
        'crudbooster::default.type_components.child.component',
        'crudbooster::default.type_components.datamodal.component',
        'crudbooster::default.type_components.upload.component',
        'crudbooster::admin_template',
        'crudbooster::menus_management',
        'crudbooster::setting',
        'crudbooster::statistic_builder.index',
    ] as $viewName) {
        $viewPath = $finder->find($viewName);
        $blade->compile($viewPath);

        expect(file_exists($blade->getCompiledPath($viewPath)))->toBeTrue();
    }
});

it('registers dynamic controller routes with expected names and slugs', function (): void {
    $controllerName = class_basename(TestRouteController::class);

    CRUDBooster::routeController('reports', $controllerName, Fixtures::class);

    expect(Route::getRoutes()->getByName($controllerName.'GetIndex')->uri())->toBe('reports')
        ->and(Route::getRoutes()->getByName($controllerName.'GetExportReport')->uri())
        ->toStartWith('reports/export-report/')
        ->and(Route::getRoutes()->getByName($controllerName.'PostSaveRecord')->uri())
        ->toStartWith('reports/save-record/');
});

it('lists package routes through the Artisan route command', function (): void {
    $loginUri = Route::getRoutes()->getByName('getLogin')->uri();

    expect(Artisan::call('route:list', ['--path' => 'login']))->toBe(0)
        ->and(Artisan::output())->toContain($loginUri)
        ->toContain('getLogin');
});

it('matches package routes from a compiled route collection', function (): void {
    $loginUri = Route::getRoutes()->getByName('getLogin')->uri();
    $compiledRoutes = Route::getRoutes()->toCompiledRouteCollection(app('router'), app());
    $matchedRoute = $compiledRoutes->match(Request::create('/'.$loginUri, 'GET'));

    expect($matchedRoute->getName())->toBe('getLogin')
        ->and($matchedRoute->getActionName())->toContain('AdminController@getLogin');
});

it('parses dynamic application controller actions and main paths', function (): void {
    Route::group([
        'prefix' => 'reports',
        'namespace' => 'App\Http\Controllers\CRUDBooster',
    ], function (): void {
        CRUDBooster::routeController('/', 'TestAppController');
    });

    $route = Route::getRoutes()->getByName('TestAppControllerGetExportReport');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toStartWith('reports/export-report/');

    $this->withoutMiddleware()->get('/reports/export-report')->assertOk();

    expect(CRUDBooster::getCurrentMethod())->toBe('getExportReport')
        ->and(CRUDBooster::mainpath())->toBe(route('TestAppControllerGetIndex'));
});

it('sends email using the template sender, recipient and subject without changing app mailer settings', function (): void {
    Cache::flush();
    Config::set('mail.mailers.smtp', ['transport' => 'array']);

    DB::table('cms_email_templates')->insert([
        'slug' => 'welcome',
        'subject' => 'Hello [name]',
        'content' => '<p>Welcome, [name]</p>',
        'from_email' => 'sender@example.test',
        'from_name' => 'Sender',
        'cc_email' => 'copy@example.test',
    ]);

    CRUDBooster::sendEmail([
        'to' => 'recipient@example.test',
        'data' => ['name' => 'Taylor'],
        'template' => 'welcome',
        'attachments' => [],
        'send_at' => null,
    ]);

    $message = app('mail.manager')->mailer('smtp')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect(Config::get('mail.mailers.smtp'))->toBe(['transport' => 'array'])
        ->and($message->getTo()[0]->getAddress())->toBe('recipient@example.test')
        ->and($message->getCc()[0]->getAddress())->toBe('copy@example.test')
        ->and($message->getFrom()[0]->getAddress())->toBe('sender@example.test')
        ->and($message->getSubject())->toBe('Hello Taylor')
        ->and($message->getHtmlBody())->toContain('Welcome, Taylor');
});

it('applies configured smtp settings and purges the resolved mailer', function (): void {
    Cache::flush();
    DB::table('cms_settings')->insert([
        ['name' => 'smtp_driver', 'content' => 'smtp'],
        ['name' => 'smtp_host', 'content' => 'smtp.example.test'],
        ['name' => 'smtp_port', 'content' => '2525'],
        ['name' => 'smtp_username', 'content' => 'test-user'],
        ['name' => 'smtp_password', 'content' => 'test-password'],
    ]);
    DB::table('cms_email_templates')->insert([
        'slug' => 'smtp-test',
        'subject' => 'SMTP test',
        'content' => 'Test message',
        'from_email' => null,
        'from_name' => null,
        'cc_email' => null,
    ]);

    $mailManager = app('mail.manager');
    $mailManager->mailer('smtp');
    Mail::fake();

    CRUDBooster::sendEmail([
        'to' => 'recipient@example.test',
        'data' => [],
        'template' => 'smtp-test',
        'attachments' => [],
        'send_at' => null,
    ]);

    expect(Config::get('mail.mailers.smtp'))->toMatchArray([
        'transport' => 'smtp',
        'host' => 'smtp.example.test',
        'port' => '2525',
        'username' => 'test-user',
        'password' => 'test-password',
    ])->and($mailManager->mailer('smtp')->getSymfonyTransport())->toBeInstanceOf(EsmtpTransport::class);
});

it('runs an explicitly requested test seeder through Artisan', function (): void {
    Artisan::call('db:seed', [
        '--class' => ExplicitSeeder::class,
        '--database' => 'testing',
        '--force' => true,
    ]);

    expect(DB::table('test_seed_markers')->where('seeded', true)->exists())->toBeTrue();
});

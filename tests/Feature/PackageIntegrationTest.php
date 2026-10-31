<?php

use crocodicstudio\crudbooster\CRUDBoosterServiceProvider;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\Fixtures;
use Tests\Fixtures\ExplicitSeeder;
use Tests\Fixtures\TestRouteController;

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

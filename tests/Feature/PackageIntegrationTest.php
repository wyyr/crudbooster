<?php

use crocodicstudio\crudbooster\CRUDBoosterServiceProvider;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use App\Http\Controllers\CRUDBooster\DynamicController as AppDynamicController;
use Tests\Fixtures\DynamicController;
use Tests\Fixtures\ExplicitSeeder;

it('boots the provider and registers package routes', function (): void {
    expect(app()->providerIsLoaded(CRUDBoosterServiceProvider::class))->toBeTrue()
        ->and(app('crudbooster'))->toBeInstanceOf(CRUDBooster::class)
        ->and(Route::has('getLogin'))->toBeTrue();
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
    CRUDBooster::routeController('reports', 'DynamicController', Tests\Fixtures::class);

    expect(Route::getRoutes()->getByName('DynamicControllerGetIndex')->uri())->toBe('reports')
        ->and(Route::getRoutes()->getByName('DynamicControllerGetExportReport')->uri())
        ->toStartWith('reports/export-report/')
        ->and(Route::getRoutes()->getByName('DynamicControllerPostSaveRecord')->uri())
        ->toStartWith('reports/save-record/');
});

it('parses the current package route action and main path', function (): void {
    Route::get('/helper-route', [AppDynamicController::class, 'getIndex'])->name('DynamicControllerGetIndex');

    $this->withoutMiddleware()->get('/helper-route')->assertOk();

    expect(CRUDBooster::getCurrentMethod())->toBe('getIndex')
        ->and(CRUDBooster::mainpath())->toBe(route('DynamicControllerGetIndex'));
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

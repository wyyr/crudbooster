<?php

namespace crocodicstudio\crudbooster;

use crocodicstudio\crudbooster\commands\CrudboosterInstallationCommand;
use crocodicstudio\crudbooster\commands\CrudboosterUpdateCommand;
use crocodicstudio\crudbooster\commands\CrudboosterVersionCommand;
use crocodicstudio\crudbooster\commands\Mailqueues;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CRUDBoosterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'crudbooster');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/localization', 'crudbooster');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->handlePublishing();
        $this->registerViewComposers();
        $this->registerCustomValidation();
        $this->registerSeedsFrom(__DIR__.'/database/seeds');

        Paginator::useBootstrap();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        if (file_exists(__DIR__.'/helpers/Helper.php')) {
            require_once __DIR__.'/helpers/Helper.php';
        }

        $this->mergeConfigFrom(__DIR__.'/config/crudbooster.php', 'crudbooster');

        $this->registerCommands();
        $this->registerAliases();

        // Core Singleton
        $this->app->singleton('crudbooster', function () {
            return new \crocodicstudio\crudbooster\helpers\CRUDBooster;
        });
    }

    private function handlePublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/crudbooster.php' => config_path('crudbooster.php'),
        ], 'cb-config');
        $this->publishes([
            __DIR__.'/userfiles/controllers/CBHook.php' => app_path('Http/Controllers/CBHook.php'),
            __DIR__.'/userfiles/controllers/AdminCmsUsersController.php' => app_path('Http/Controllers/AdminCmsUsersController.php'),
        ], 'cb-controllers');
        $this->publishes([
            __DIR__.'/public' => public_path(),
        ], 'cb-assets');
        $this->publishes([
            __DIR__.'/database/seeders/CBSeeder.php' => database_path('seeders/CBSeeder.php'),
        ], 'cb-seeders');
    }

    private function registerViewComposers(): void
    {
        View::composer('crudbooster::admin_template', function ($view) {
            $view->with([
                'page_icon' => $view->page_icon ?? '',
                'sidebar_mode' => $view->sidebar_mode ?? '',
                'style_css' => $view->style_css ?? '',
                'load_css' => $view->load_css ?? [],
                'load_js' => $view->load_js ?? [],
                'script_js' => $view->script_js ?? '',
            ]);
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudboosterInstallationCommand::class,
                CrudboosterUpdateCommand::class,
                CrudboosterVersionCommand::class,
                Mailqueues::class,
            ]);
        }
    }

    private function registerAliases(): void
    {
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();

            $aliases = [
                'PDF' => \Barryvdh\DomPDF\Facade\Pdf::class,
                'Excel' => \Maatwebsite\Excel\Facades\Excel::class,
                'Image' => \Intervention\Image\Facades\Image::class,
                'CRUDBooster' => \crocodicstudio\crudbooster\helpers\CRUDBooster::class,
                'CB' => \crocodicstudio\crudbooster\helpers\CB::class,
            ];

            foreach ($aliases as $alias => $class) {
                if (class_exists($class)) {
                    $loader->alias($alias, $class);
                }
            }
        });
    }

    protected function registerSeedsFrom(string $path): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (! str_contains(implode(' ', request()->server('argv', [])), 'db:seed')) {
            return;
        }

        $files = File::glob("$path/*.php");
        foreach ($files as $filename) {
            require_once $filename;
            $class = basename($filename, '.php');
            if (class_exists($class)) {
                Artisan::call('db:seed', ['--class' => $class]);
            }
        }
    }

    private function registerCustomValidation(): void
    {
        Validator::extend('alpha_spaces', function ($attribute, $value) {
            return preg_match('/^[\pL\s]+$/u', $value);
        }, trans('crudbooster::crudbooster.alpha_spaces'));

        Validator::extend('alpha_num_spaces', function ($attribute, $value) {
            return preg_match('/^[a-zA-Z0-9\s]+$/', $value);
        }, trans('crudbooster::crudbooster.alpha_num_spaces'));
    }
}

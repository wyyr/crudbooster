<?php

namespace Tests;

use Barryvdh\DomPDF\ServiceProvider as DompdfServiceProvider;
use crocodicstudio\crudbooster\CRUDBoosterServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\ExcelServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CRUDBoosterServiceProvider::class,
            DompdfServiceProvider::class,
            ExcelServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('mail.default', 'smtp');
        $app['config']->set('mail.mailers.smtp', [
            'transport' => 'array',
        ]);

        Schema::connection('testing')->create('cms_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->unsignedBigInteger('id_cms_privileges');
            $table->string('status');
            $table->timestamps();
        });
        Schema::connection('testing')->create('cms_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('content')->nullable();
        });
        Schema::connection('testing')->create('cms_email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('subject');
            $table->text('content');
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('cc_email')->nullable();
        });
        Schema::connection('testing')->create('test_seed_markers', function (Blueprint $table): void {
            $table->id();
            $table->boolean('seeded');
        });
        Schema::connection('testing')->create('cms_apikey', function (Blueprint $table): void {
            $table->id();
            $table->string('screetkey')->nullable();
            $table->integer('hit')->nullable();
            $table->string('status', 25)->default('active');
            $table->timestamps();
        });
        Schema::connection('testing')->create('test_relation_modules', function (Blueprint $table): void {
            $table->id();
        });
        Schema::connection('testing')->create('test_related_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('label');
            $table->boolean('active')->nullable();
        });
    }
}

<?php

namespace Ndexondeck\Lauditor;

use Illuminate\Support\ServiceProvider;
use Ndexondeck\Lauditor\Console\DatabaseFlush;
use Ndexondeck\Lauditor\Console\TaskGenerate;

class LauditorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__.'/../config';
        $modelPath = __DIR__.'/../sample/Model';
        $migrationPath = __DIR__.'/../migrations';
        $viewPath = __DIR__.'/../views';
        $publishedMigrationPath = __DIR__.'/../published_migrations';
        $publishedUtilities = __DIR__.'/Ndexondeck';
        $publishedControllerPath = __DIR__.'/../published_controllers';
        $publishedApiControllerPath = __DIR__.'/../published_controllers/Api';
        $routesPath = __DIR__.'/../routes.php';
        $seederPath = __DIR__.'/../sample/LauditorSetupSeeder.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => config_path('ndexondeck'),
                $publishedMigrationPath => database_path('migrations'),
                $publishedControllerPath => app_path('Http/Controllers'),
                $publishedUtilities => app_path('Ndexondeck'),
                $modelPath => app_path(''),
                $seederPath => database_path('seeds/LauditorSetupSeeder.php'),
            ], 'ndexondeck-lauditor');

            $this->publishes([
                $publishedApiControllerPath => app_path('Http/Controllers'),
            ], 'ndexondeck-lauditor-apic');
        }

        $this->loadMigrationsFrom($migrationPath);

        $this->loadViewsFrom($viewPath,'lauditor');

        $this->loadRoutesFrom($routesPath);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->commands([
            TaskGenerate::class,
            DatabaseFlush::class,
        ]);
    }
}

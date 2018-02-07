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
        $configPath = __DIR__.'/../config/lauditor.php';
        $modelPath = __DIR__.'/../sample/Model';
        $migrationPath = __DIR__.'/../migrations';
        $viewPath = __DIR__.'/../views';
        $publishedMigrationPath = __DIR__.'/../published_migrations';
        $publishedUtilities = __DIR__.'/Ndexondeck';
        $publishedControllerPath = __DIR__.'/../published_controllers';
        $routesPath = __DIR__.'/../published_routes/routes.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => config_path('ndexondeck/lauditor.php'),
                $publishedMigrationPath => database_path('migrations'),
                $publishedControllerPath => app_path('Http/Controllers'),
                $publishedUtilities => app_path('Ndexondeck'),
                $modelPath => app_path(''),
            ], 'ndexondeck-lauditor');
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

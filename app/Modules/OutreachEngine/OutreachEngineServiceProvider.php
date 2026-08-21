<?php

namespace App\Modules\OutreachEngine;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the OutreachEngine ("Lead Finder") module into the mother app.
 *
 * This provider MUST be listed before App\Providers\RouteServiceProvider in
 * config/app.php: routes/web.php ends with a Route::get('{any}') catch-all, and
 * whichever provider registers first wins the URL.
 */
class OutreachEngineServiceProvider extends ServiceProvider
{
    /**
     * Artisan commands shipped by the module.
     *
     * Cron is the module's real execution path (QUEUE_CONNECTION is sync, so a
     * dispatch() would block the request that made it), which is why these are
     * first-class rather than an afterthought.
     *
     * @var array<int, class-string>
     */
    protected $moduleCommands = [
        \App\Modules\OutreachEngine\Console\Commands\ScrapeGridsCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\EnrichLeadsCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\CategorizeLeadsCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\VerifyEmailsCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\ProcessQueueCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\FetchRepliesCommand::class,
        \App\Modules\OutreachEngine\Console\Commands\OutreachStatusCommand::class,
    ];

    /**
     * Register any module services.
     *
     * Nothing is bound here on purpose. Every service either auto-wires or is
     * constructed with an already-resolved OutreachSetting by its caller, so a
     * container binding would only add a boot-time cost to requests that never
     * touch the module.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap the module.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Resources/Views', 'outreach');

        // The route file itself declares no middleware group; it is applied here
        // so the module's pages get sessions, cookies and CSRF like any other
        // admin page. Individual routes add 'auth' themselves.
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        });

        if ($this->app->runningInConsole()) {
            // Only register commands whose class is actually present. Artisan
            // resolves registered commands eagerly, so a single missing class
            // here would throw a BindingResolutionException on EVERY artisan
            // call — including `migrate`, the one command needed to recover.
            // A module file going missing should degrade this module, not the
            // mother app's entire CLI.
            $this->commands(array_filter($this->moduleCommands, 'class_exists'));
        }
    }
}

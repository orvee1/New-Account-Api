<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Menu;
use App\Observers\CompanyObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1) Company Observer attach (এটাই default COA অটো-সিড করবে)
        Company::observe(CompanyObserver::class);

        // 2) Production-এ HTTPS force
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            // অনেক সময় proxy-র পেছনে হলে:
            request()->server->set('HTTPS', 'on');
        }

        // 3) Admin মেনু শেয়ার (cache সহ)
        View::share('menus', $this->getAdminMenus());
    }

    /**
     * Cached Admin menus helper.
     */
    protected function getAdminMenus()
    {
        return Cache::rememberForever('AdminPanelMenus', function () {
            return Menu::query()
                ->with('submenu.thirdmenu')
                ->mainMenu()
                ->orderBy('title')
                ->get();
        });
    }
}

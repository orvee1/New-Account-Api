<?php

namespace App\Providers;

use App\Models\Menu;
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
    public function menus()
    {

        return Cache::rememberForever('AdminPanelMenus', function () {
            return Menu::query()
                ->with('submenu.thirdmenu')
                ->mainMenu()
                ->orderBy('title')
                ->get();
        });
    }

    public function boot()
    {

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if (env('APP_ENV') == 'production') {
            $this->app['request']->server->set('HTTPS', 'on');
        }

        View::share('menus', $this->menus());
    }
}

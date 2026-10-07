<?php

namespace App\Providers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Schema;
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
        // The announcements banner (home page and player dashboard) always shows
        // the announcements that have not expired yet, newest first.
        View::composer('partials.announcements', function ($view) {
            $view->with('announcements', Schema::hasTable('announcements')
                ? Announcement::active()->latest()->get()
                : collect());
        });
    }
}

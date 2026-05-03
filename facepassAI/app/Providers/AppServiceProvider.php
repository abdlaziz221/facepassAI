<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // 125 = limite max pour un index unique sur (name, guard_name)
        // en utf8mb4 sur MySQL avec ROW_FORMAT=COMPACT (125 * 4 * 2 = 1000 octets).
        Schema::defaultStringLength(125);
    }
}

<?php

namespace App\Providers;

use App\Listeners\CreateWalletForNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            CreateWalletForNewUser::class,
        ],
    ];
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
        //
    }
}

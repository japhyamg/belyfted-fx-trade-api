<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\AccountRepository;
use App\Repositories\Contracts\AccountRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

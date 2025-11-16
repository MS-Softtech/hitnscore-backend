<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Connector\Dashboard\LiveMatchInterface;
use App\Http\Repository\Dashboard\LiveMatchRepository;
use App\Http\Connector\Auth\AuthSocialRepositoryInterface;
use App\Http\Connector\Auth\AuthSocialServiceInterface as AuthAuthSocialServiceInterface;
use App\Http\Repository\Auth\AuthSocialRepository;
use App\Http\Service\Auth\AuthSocialService as AuthAuthSocialService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Http\Connector\Auth\AuthInterface::class,
            \App\Http\Repository\Auth\AuthRepository::class
            
        );
        $this->app->bind(LiveMatchInterface::class, LiveMatchRepository::class);
        $this->app->bind(AuthSocialRepositoryInterface::class, AuthSocialRepository::class);
        $this->app->bind(AuthAuthSocialServiceInterface::class, AuthAuthSocialService::class);
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Models\AccessToken;
use App\Models\DepositReceipt;
use App\Models\Report;
use App\Models\UserWallet;
use App\Models\Repository\Eloquent\BankRepositoryEloquent;
use App\Models\User;
use App\Observers\AccessTokenObserver;
use App\Observers\DepositReceiptObserver;
use App\Observers\ReportObserver;
use App\Observers\UserObserver;
use App\Observers\UserWalletObserver;
use App\Models\Repository\Eloquent\UserRepositoryEloquent;
use App\Models\Repository\UserBankRepository;
use App\Models\Repository\UserRepository;
use Illuminate\Support\Facades\Http;
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
        $this->app->bind(UserRepository::class, UserRepositoryEloquent::class);
        $this->app->bind(UserBankRepository::class, BankRepositoryEloquent::class);
        Report::observe(ReportObserver::class);
        User::observe(UserObserver::class);
        AccessToken::observe(AccessTokenObserver::class);
        DepositReceipt::observe(DepositReceiptObserver::class);
        UserWallet::observe(UserWalletObserver::class);

        Http::macro('textapi', function(){
            return Http::acceptJson()->baseUrl(config('textapi.url'));
        });
    }
}

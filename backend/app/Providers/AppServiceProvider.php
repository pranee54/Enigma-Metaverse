<?php

namespace App\Providers;

use App\Models\Configuration;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Configuration::flushBag();
        Paginator::useTailwind();

        View::composer(['layouts.app', 'layouts.auth', 'auth.login', 'dashboard'], function ($view) {
            try {
                // getValue loads the whole tiny configs table once per request
                $view->with([
                    'companyName' => Configuration::getValue('company_name', 'Enigma Payroll'),
                    'companyState' => Configuration::getValue('company_state', 'Telangana'),
                    'companyCurrency' => Configuration::getValue('currency', 'INR'),
                ]);
            } catch (Throwable) {
                $view->with([
                    'companyName' => 'Enigma Payroll',
                    'companyState' => 'Telangana',
                    'companyCurrency' => 'INR',
                ]);
            }
        });
    }
}

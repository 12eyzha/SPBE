<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
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
        /*
        |--------------------------------------------------------------------------
        | Locale Carbon
        |--------------------------------------------------------------------------
        |
        | Membuat translatedFormat() menggunakan Bahasa Indonesia.
        |
        */

        Carbon::setLocale('id');

        /*
        |--------------------------------------------------------------------------
        | Password Reset URL
        |--------------------------------------------------------------------------
        |
        | Laravel akan membuat token reset password dan mengirim email.
        | URL pada email diarahkan ke frontend React, bukan ke backend
        | Laravel.
        |
        */

        ResetPassword::createUrlUsing(
            function ($notifiable, string $token): string {
                $email = urlencode(
                    $notifiable->getEmailForPasswordReset()
                );

                return config('app.frontend_url')
                    . '/reset-password/'
                    . $token
                    . '?email='
                    . $email;
            }
        );
    }
}
<?php

namespace App\Providers;

use App\Models\User;
use Auth;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
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

        Fortify::authenticateUsing(function ($request) {
            $username = $request->username;
            $password = $request->password;

            $user = User::where('username', $username)->first();

            if ($user) {
                if (Hash::check($password, $user->password)) {
                    Auth::guard('web')->login($user, $request->boolean('remember'));
                    return Auth::guard('web')->user();
                }
            } elseif (!$user) {
                // Usuário não encontrado no banco de dados local, não permite o login
                return null;

            }

            $validated = Auth::validate([
                'samaccountname' => $request->username,
                'password' => $request->password
            ]);

            return $validated ? Auth::getLastAttempted() : null;
        });

        Fortify::loginView(function () {
            return inertia('auth/Login'); 
        });
        
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

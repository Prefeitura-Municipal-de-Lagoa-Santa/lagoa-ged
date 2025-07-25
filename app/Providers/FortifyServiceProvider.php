<?php

namespace App\Providers;


use App\Models\User;
use Auth;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        Group::class => GroupPolicy::class, 
        User::class => UserPolicy::class, 
        Document::class => DocumentPolicy::class, 
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Fortify::authenticateUsing(function ($request) {
            $username = $request->username;
            $password = $request->password;
            
            $localUser = User::where('username', $username)->first();

            if ($localUser && Hash::check($password, $localUser->password)) {
                Auth::guard('web')->login($localUser,  $request->boolean('remember'));
                //dd(Auth::user()->username);
                return $localUser;
            }

            $ldapAuthenticated  = Auth::guard('web')->attempt([
                'samaccountname' => $request->username,
                'password' => $request->password
            ]);

            return $ldapAuthenticated ? Auth::getLastAttempted() : null;
        });
        
        //dd('FortifyServiceProvider boot() method ESTÁ SENDO EXECUTADO');
        // Actions padrão do Fortify (mantenha estas linhas)
        //Fortify::createUsersUsing(CreateNewUser::class);
        //Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        //Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        //Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // --- ADICIONE AS DEFINIÇÕES DE VIEW DO INERTIA AQUI ---
        Fortify::loginView(function () {
            return inertia('auth/Login'); // Ex: resources/js/Pages/Auth/Login.vue
        });
//
        //Fortify::registerView(function () {
        //    return inertia('auth/Register'); // Ex: resources/js/Pages/Auth/Register.vue
        //});
//
        //Fortify::requestPasswordResetLinkView(function () {
        //    return inertia('auth/ForgotPassword'); // Ex: resources/js/Pages/Auth/ForgotPassword.vue
        //});
//
        //Fortify::resetPasswordView(function (Request $request) {
        //    return inertia('auth/ResetPassword', ['token' => $request->route('token')]); // Ex: resources/js/Pages/Auth/ResetPassword.vue
        //});

        // Verifique se o feature está habilitado no config/fortify.php
        //if (Fortify::enabled(FortifyFeatures::emailVerification())) {
        //Fortify::verifyEmailView(function () {
        //return inertia('Auth/VerifyEmail'); // Ex: resources/js/Pages/Auth/VerifyEmail.vue
        //});
        //}

        // if (Fortify::enabled(FortifyFeatures::confirmPasswords())) {
        //     Fortify::confirmPasswordView(function () {
        //         return inertia('Auth/ConfirmPassword');
        //     });
        // }

        // if (Fortify::enabled(FortifyFeatures::twoFactorAuthentication())) {
        //     Fortify::twoFactorChallengeView(function () {
        //         return inertia('Auth/TwoFactorChallenge');
        //     });
        // }
        // --- FIM DAS DEFINIÇÕES DE VIEW DO INERTIA ---


        // Rate limiters padrão do Fortify (mantenha estas seções)
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

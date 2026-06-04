<?php

namespace App\Providers;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Illuminate\Support\Facades\Gate;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // 💡 En desarrollo local, eliminamos los middlewares que exigen sesión web.
        // Como Nginx ya protege el puerto 8001, Laravel puede dar acceso libre.
        if (app()->environment('local')) {
            config(['horizon.middleware' => ['web']]);
        }
    }

    /**
     * Autorización de Horizon.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            // Paso libre en local porque Nginx ya validó al usuario en el puerto 8001
            if (app()->environment('local')) {
                return true;
            }

            return Gate::check('viewHorizon', [$request->user()]);
        });
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return in_array($user->email, [
                'admin@admin.com',
            ]);
        });
    }
}

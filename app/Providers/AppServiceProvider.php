<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('manage-users', fn (User $user) => $user->canAccess('users.manage'));
        Gate::define('manage-roles', fn (User $user) => $user->canAccess('users.manage'));
        Gate::define('manage-settings', fn (User $user) => $user->canAccess('settings.manage'));
        Gate::define('view-audit', fn (User $user) => $user->canAccess('audit.view'));
    }
}

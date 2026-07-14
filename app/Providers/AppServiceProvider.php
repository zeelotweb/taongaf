<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Services\CommerceService;
use App\Services\PurchaseService;


use App\Models\User;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
      



    $this->app->bind(PurchaseService::class, function () {
        return new PurchaseService(new CommerceService());
    });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();


        // Superadmin can do everything
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'superadmin') return true;
        });

        // Who can access admin panel
        Gate::define('access-admin', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin', 'staff']);
        });

        // Who can manage content (create/edit/delete)
        Gate::define('manage-content', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin']);
        });

        // Who can manage users
        Gate::define('manage-users', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin']);
        });

        // Who can assign roles
        Gate::define('assign-roles', function (User $user) {
            return $user->role === 'superadmin';
        });

        // Who can publish content
        Gate::define('publish-content', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin']);
        });

        // Who can delete content
        Gate::define('delete-content', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin']);
        });

        // Publishers — community users who publish
        Gate::define('publish-community', function (User $user) {
            return in_array($user->role, ['superadmin', 'admin', 'publisher']);
        });











// Studio gates
Gate::define('access-studio', function (User $user) {
    return $user->hasActiveStudio();
});

Gate::define('manage-studio-staff', function (User $user) {
    return $user->hasActiveStudio();
});

Gate::define('studio-content-manager', function (User $user, User $publisher) {
    return $user->id === $publisher->id
        || $user->hasStudioRole($publisher, 'content_manager');
});

Gate::define('studio-reaction-moderator', function (User $user, User $publisher) {
    return $user->id === $publisher->id
        || $user->hasStudioRole($publisher, 'reaction_moderator');
});

Gate::define('studio-community-moderator', function (User $user, User $publisher) {
    return $user->id === $publisher->id
        || $user->hasStudioRole($publisher, 'community_moderator');
});

Gate::define('studio-content-analyst', function (User $user, User $publisher) {
    return $user->id === $publisher->id
        || $user->hasStudioRole($publisher, 'content_analyst');
});

    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}









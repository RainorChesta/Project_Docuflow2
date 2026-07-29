<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
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
        Gate::define('admin', fn(User $user) => $user->isAdmin());

        Gate::policy(Document::class, DocumentPolicy::class);
    }
}

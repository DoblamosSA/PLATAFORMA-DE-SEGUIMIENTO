<?php

namespace App\Providers;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Observers\SubtaskObserver;
use App\Observers\TaskObserver;
use Illuminate\Support\Facades\Gate;
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
        // Web Push: notifica cualquier cambio en tareas y subtareas.
        Task::observe(TaskObserver::class);
        Subtask::observe(SubtaskObserver::class);

        Gate::define('admin', fn (User $user) => $user->esAdmin());
    }
}

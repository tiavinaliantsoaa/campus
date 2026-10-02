<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AcademicYearContext;
use App\Support\Navigation;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AcademicYearContext::class);
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url');

        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);
        }

        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('components.pagination');

        Gate::before(function ($user) {
            return $user instanceof User && $user->hasRole('administration') ? true : null;
        });

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if (! $user instanceof User) {
                return;
            }

            $year = app(AcademicYearContext::class)->current();

            $view->with([
                'currentYear' => $year,
                'availableYears' => AcademicYear::query()->orderByDesc('starts_on')->orderByDesc('id')->get(),
                'navItems' => Navigation::for($user),
                'attendanceBadge' => Navigation::attendanceBadge($user, $year),
            ]);
        });
    }
}

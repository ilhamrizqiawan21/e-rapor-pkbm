<?php

namespace App\Providers;

use App\Contracts\PdfEngine;
use App\Models\AcademicClass;
use App\Services\AcademicPeriodContext;
use App\Services\InstitutionBrandingService;
use App\Services\Reports\ChromePdfEngine;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PdfEngine::class, fn (): PdfEngine => new ChromePdfEngine(
            (string) config('report_pdf.browser_binary', 'google-chrome'),
            (int) config('report_pdf.timeout_seconds', 30),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('security.force_https')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            $user = request()->user();
            $period = app(AcademicPeriodContext::class)->forInstitution($user?->institution_id);
            $branding = app(InstitutionBrandingService::class)->resolve($user);

            $view->with([
                'period' => $period,
                'hasPeriod' => $period['academicYear'] !== null && $period['semester'] !== null,
                'hasHomeroomClass' => $user?->hasRole('GURU') && AcademicClass::query()->where('homeroom_teacher_id', $user->id)->exists(),
                'brandingInstitution' => $branding,
                'brandingLogoUrl' => app(InstitutionBrandingService::class)->logoUrl($branding),
            ]);
        });

        View::composer('layouts.guest', function ($view): void {
            $branding = app(InstitutionBrandingService::class)->resolve();

            $view->with([
                'brandingInstitution' => $branding,
                'brandingLogoUrl' => app(InstitutionBrandingService::class)->logoUrl($branding),
            ]);
        });
    }
}

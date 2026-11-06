<?php

namespace App\Providers;

use App\Contracts\PdfEngine;
use App\Services\Reports\ChromePdfEngine;
use Illuminate\Support\Facades\URL;
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
    }
}

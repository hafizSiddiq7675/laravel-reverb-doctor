<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor;

use HmzaUsman\LaravelReverbDoctor\Commands\ReverbDoctorCommand;
use Illuminate\Support\ServiceProvider;

class ReverbDoctorServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReverbDoctorCommand::class,
            ]);
        }
    }
}

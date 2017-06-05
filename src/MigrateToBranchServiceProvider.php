<?php

namespace Gilbitron\Laravel;

use Illuminate\Support\ServiceProvider;

class MigrateToBranchServiceProvider extends ServiceProvider
{
    protected $commands = [
        \Gilbitron\Laravel\Console\Commands\MigrateToBranch::class,
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
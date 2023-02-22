<?php

namespace MobileNowGroup\LaravelAliyunSms;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Determin is defer.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @author Fayne Guo <fayne.guo@gmail.com>
     */
    public function boot()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/sms.php' => config_path('sms.php'), ],
                'sms'
            );

            $this->publishes([
                dirname(__DIR__).'/config/aliyun.php' => config_path('aliyun.php'), ],
                'aliyun'
            );

            $this->commands([
                Console\CreateSignCommand::class,
                Console\CreateTemplateCommand::class,
            ]);

            $this->registerMigrations();
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('aliyun.sms', function () {
            return AliyunSms::make(config('sms'));
        });

        $this->offerPublishing();
    }

    /**
     * Register migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup the resource publishing groups for Passport.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js'),
        ], 'resources');
    }
}

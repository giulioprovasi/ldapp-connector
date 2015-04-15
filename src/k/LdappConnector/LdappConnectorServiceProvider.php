<?php namespace k\LdappConnector;

use Illuminate\Auth\Guard;
use Illuminate\Support\ServiceProvider;
use Auth;
use Exception;

class LdappConnectorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        Auth::extend('ldapp', function ($app) {
            $provider = new LdappUserProvider($this->getConfig(), $this->app['config']['auth.model']);
            $provider->setIdentifier($this->getConfig('plus.identifier'));
            return new Guard($provider, $app['session.store']);
        });
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return array('auth');
    }

    /**
     * Helper to get the config values
     * @param  string|null $key
     * @param  string|null $default
     * @return mixed
     * @throws Exception thrown on missing configuration file
     */
    public function getConfig($key = null, $default = null)
    {
        if (!$this->app['config']['ldapp']) {
            throw new Exception('LDAP+ config not found. Check if app/config/ldapp.php exists.');
        }

        return config(sprintf('ldapp%s', ($key ? '.'.$key : '')), $default);
    }
}

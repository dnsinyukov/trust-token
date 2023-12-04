<?php

namespace Coderden\TrustToken;

use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Factory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PackageServiceProvider extends ServiceProvider
{

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/trust-token.php' => config_path('trust-token.php'),
        ]);

        Auth::resolved(function (Factory $auth) {
            $auth->extend('trust-token', function (Application $app, string $name, array $config) use ($auth) {

                $guard = new RequestGuard(
                    new Guard($auth, $app['config']['trust-token']['expiration'] ?? null),
                    $app->get('request'),
                    $auth->createUserProvider($config['provider'] ?? null)
                );

                return tap($guard, function ($guard) use ($app) {
                    $app->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/trust-token.php', 'trust-token'
        );

        $this->app->resolving('hash', function() {
            $this->app->bind(TokenRepositoryInterface::class, function () {
                return $this->createTokenRepository();
            });
        });

    }

    /**
     * Create a token repository instance based on the given configuration.
     *
     * @return TokenRepositoryInterface
     */
    protected function createTokenRepository(): TokenRepositoryInterface
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $config = $this->app['config'];

        return new TokenRepository(
            $this->app['db']->connection($config['database']['default']),
            $config['trust-token']['table'] ?? 'access_tokens',
            $key
        );
    }
}

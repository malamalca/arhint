<?php
declare(strict_types=1);

namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\I18n\Time;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Lil\Plugin as LilPlugin;
use LilCrm\Plugin as LilCrmPlugin;
use LilExpenses\Plugin as LilExpensesPlugin;
use LilInvoices\Plugin as LilInvoicesPlugin;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    public const REMEMBERME_COOKIE_NAME = 'Arhint';

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }

        /*
         * Only try to load DebugKit in development mode
         * Debug Kit should not be installed on a production system
         */
        if (Configure::read('debug') && (PHP_SAPI != 'cli')) {
            $this->addPlugin('DebugKit');
        }

        if (PHP_SAPI == 'cli') {
            $this->addPlugin('Migrations');
        }

        // Load more plugins here
        $this->addPlugin(LilPlugin::class, ['bootstrap' => true, 'routes' => true]);
        $this->addPlugin(LilCrmPlugin::class, ['bootstrap' => true, 'routes' => true]);
        $this->addPlugin(LilExpensesPlugin::class, ['bootstrap' => true, 'routes' => true]);
        $this->addPlugin(LilInvoicesPlugin::class, ['bootstrap' => true, 'routes' => true]);
    }

    /**
     * Build routes
     *
     * @param \Cake\Routing\RouteBuilder $routes Route builder
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->setRouteClass(DashedRoute::class);

        $routes->scope('/', function (RouteBuilder $builder) {
            $builder->connect('/', ['plugin' => 'LilCrm', 'controller' => 'Contacts', 'action' => 'index']);
            //$builder->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

            /*
             * Connect catchall routes for all controllers.
             *
             * The `fallbacks` method is a shortcut for
             *
             * ```
             * $builder->connect('/:controller', ['action' => 'index']);
             * $builder->connect('/:controller/:action/*', []);
             * ```
             *
             * You can remove these routes once you've connected the
             * routes you want in your application.
             */
            $builder->fallbacks();
        });
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance. For that when
            // creating the middleware instance specify the cache config name by
            // using it's second constructor argument:
            // `new RoutingMiddleware($this, '_cake_routes_')`
            ->add(new RoutingMiddleware($this))

            ->add(new RoutingMiddleware($this, '_cake_routes_'))
            ->add(new CsrfProtectionMiddleware([]))
            ->add(new EncryptedCookieMiddleware([self::REMEMBERME_COOKIE_NAME], Configure::read('Security.cookieKey')))
            ->add(new AuthenticationMiddleware($this))
            ->add(new AuthorizationMiddleware($this, [
                'unauthorizedHandler' => [
                    'className' => 'Authorization.Redirect',
                    'url' => '/users/login',
                    'queryParam' => 'redirectUrl',
                    'exceptions' => [
                        MissingIdentityException::class,
                    ],
                ],
            ]));

        if (Configure::read('debug')) {
            // Disable authz for debugkit
            $middlewareQueue->add(function ($req, $res, $next) {
                if ($req->getParam('plugin') === 'DebugKit') {
                    $req->getAttribute('authorization')->skipAuthorization();
                }

                return $next($req, $res);
            });
        }

        return $middlewareQueue;
    }

    /**
     * Bootrapping for CLI application.
     *
     * That is when running commands.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        try {
            $this->addPlugin('Bake');
        } catch (MissingPluginException $e) {
            // Do not halt if the plugin is missing
        }

        $this->addPlugin('Migrations');

        // Load more plugins here
    }

    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => Configure::read('loginPath', 'users/login'),
            'queryParam' => 'redirect',
        ]);

        $fields = [
            'username' => 'username',
            'password' => 'passwd',
        ];

        // Load identifiers
        $service->loadIdentifier('Authentication.Password', [
            'fields' => $fields,
            'passwordHasher' => [
                'className' => 'Authentication.Fallback',
                'hashers' => [
                    'Authentication.Default',
                    [
                        'className' => 'Authentication.Legacy',
                        'hashType' => 'sha1',
                    ],
                ],
            ],
        ]);

        // Load the authenticators, you want session first
        $service->loadAuthenticator('Authentication.Session');

        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => Router::url('/users/login'),
        ]);

        $service->loadAuthenticator('Authentication.Cookie', [
            'fields' => $fields,
            'cookie' => [
                'name' => self::REMEMBERME_COOKIE_NAME,
                'expire' => (new Time())->addDays(30),
            ],
            'loginUrl' => Router::url('/users/login'),
        ]);

        return $service;
    }

    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        //$resolver = new MapResolver();
        //$resolver->map(ServerRequest::class, RequestPolicy::class);

        $resolver = new OrmResolver();

        return new AuthorizationService($resolver);
    }
}

<?php
declare(strict_types=1);

namespace App;

use App\Command\HeartBeatCommand;
use App\Event\AppEvents;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\Middleware\SessionCsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @extends \Cake\Http\BaseApplication<\App\Application>
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    public const REMEMBERME_COOKIE_NAME = 'ARHINT';

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI !== 'cli') {
            // The bake plugin requires fallback table classes to work properly
            FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
        }
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

        $routes->setExtensions(['json', 'aht', 'xml', 'pdf', 'txt', 'png']);

        $routes->scope('/', function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Pages', 'action' => 'dashboard']);

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
        $csrf = new SessionCsrfProtectionMiddleware();
        $csrf->skipCheckCallback(function ($request) {
            return $this->checkParams($request->getAttribute('params'), [
                ['controller' => 'ProjectsWorkhours', 'action' => 'import'],
                ['controller' => 'Projects', 'action' => 'linkEmail'],
                ['controller' => ['Invoices', 'Documents'], 'action' => 'edit', $request->hasHeader('Lil-Scan')],
            ]);
        });

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

        ->add($csrf)
        ->add(new EncryptedCookieMiddleware([self::REMEMBERME_COOKIE_NAME], Configure::read('Security.cookieKey')))
        ->add(new AuthenticationMiddleware($this))
        ->add(new AuthorizationMiddleware($this, [
            'unauthorizedHandler' => [
                'className' => 'Authorization.Redirect',
                'url' => $this->getLoginPath(),
                'queryParam' => 'redirect',
                'exceptions' => [
                    MissingIdentityException::class,
                ],
            ],
            'identityDecorator' => function ($auth, $user) {
                return $user->setAuthorization($auth);
            },
        ]));

        return $middlewareQueue;
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        $eventManager->on(new AppEvents());

        return $eventManager;
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
            'unauthenticatedRedirect' => $this->getLoginPath(),
            'loginUrl' => $this->getLoginPath(),
            'queryParam' => 'redirect',
        ]);

        $fields = [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'username',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'passwd',
        ];

        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => [$this->getLoginPath()],
        ]);

        $service->loadAuthenticator('Authentication.Session');

        $service->loadAuthenticator('Authentication.Cookie', [
            'fields' => $fields,
            'cookie' => [
                'name' => self::REMEMBERME_COOKIE_NAME,
                'expires' => (new DateTime())->addDays(30)->toDateTimeString(),
            ],
        ]);

        if (
            $this->checkParams($request->getAttribute('params'), [
            ['controller' => 'Projects', 'action' => 'index', '_ext' => ['txt', 'json', 'xml']],
            ['controller' => 'Projects', 'action' => 'linkEmail', '_ext' => 'json'],
            ['controller' => 'Projects', 'action' => 'view', '_ext' => 'txt'],
            ['controller' => 'Projects', 'action' => 'view', '_ext' => 'xml'],
            ['controller' => 'Calendars', 'action' => 'view'],
            ['controller' => 'ProjectsWorkhours', 'action' => 'import'],
            ['controller' => ['Invoices', 'Documents'], 'action' => 'edit'],
            ])
        ) {
            $service->loadAuthenticator('Authentication.HttpBasic', ['realm' => 'intranet', 'fields' => $fields]);
        }

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
        $ormResolver = new OrmResolver();

        return new AuthorizationService($ormResolver);
    }

    /**
     * Setup console actions
     *
     * @param \Cake\Console\CommandCollection $commands Commands
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        parent::console($commands);

        $commands->add('hourly', HeartBeatCommand::class);

        return $commands;
    }

    /**
     * Validate groups of param values
     *
     * @param array<array-key, mixed> $params Array of passed params (request); there is an OR condition
     * @param array<array-key, mixed> $checkList Checklist array
     * @return bool
     */
    private function checkParams(array $params, array $checkList): bool
    {
        $result = true;
        foreach ($checkList as $conditions) {
            foreach ($conditions as $paramName => $paramValue) {
                if (is_numeric($paramName) && !$paramValue) {
                    $result = false;
                }
                if (isset($params[$paramName]) || (isset($params[$paramName]) && is_null($params[$paramName]))) {
                    if (is_array($paramValue) && !in_array($params[$paramName], $paramValue)) {
                        $result = false;
                    }
                    if (is_string($paramValue) && $params[$paramName] !== $paramValue) {
                        $result = false;
                    }
                }
            }

            if ($result === true) {
                return true;
            }
            $result = true;
        }

        return false;
    }

    /**
     * Returns login path
     *
     * @return string
     */
    private function getLoginPath(): string
    {
        return Router::url('/users/login');
    }
}

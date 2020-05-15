<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  RoutingManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/routingmanager
 * @since     Version 0.1
 */

namespace Flight\Routing\Interfaces;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Flight\Routing\Exceptions\RouteNotFoundException;
use Flight\Routing\Exceptions\UrlGenerationException;
use Flight\Routing\Middlewares\MiddlewareDisptcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

interface RouteCollectorInterface
{
    public const TYPE_REQUIREMENT = 1;
    public const TYPE_DEFAULT = 0;

    /**
     * Characters that should not be URL encoded.
     *
     * @var array
     */
    public const DONT_ENCODE = [
        // RFC 3986 explicitly allows those in the query/fragment to reference other URIs unencoded
        '%2F' => '/',
        '%3F' => '?',
        // reserved chars that have no special meaning for HTTP URIs in a query or fragment
        // this excludes esp. "&", "=" and also "+" because PHP would treat it as a space (form-encoded)
        '%40' => '@',
        '%3A' => ':',
        '%21' => '!',
        '%3B' => ';',
        '%2C' => ',',
        '%2A' => '*',
        '%3D' => '=',
        '%2B' => '+',
        '%7C' => '|',
        '%26' => '&',
        '%23' => '#',
        '%25' => '%',
    ];

    /**
     * Get route objects
     *
     * @return RouteInterface[]|iterable
     */
    public function getRoutes(): iterable;

    /**
     * Generate a URI from the named route.
     *
     * Takes the named route and any parameters, and attempts to generate a
     * URI from it. Additional router-dependent query may be passed.
     *
     * Once there are no missing parameters in the URI we will encode
     * the URI and prepare it for returning to the user. If the URI is supposed to
     * be absolute, we will return it as-is. Otherwise we will remove the URL's root.
     *
     * @param string         $routeName  route name
     * @param string[]|array $parameters key => value option pairs to pass to the
     *                                   router for purposes of generating a URI; takes precedence over options
     *                                   present in route used to generate URI
     * @param array         $queryParams Optional query string parameters
     *
     * @return string of fully qualified URL for named route.
     *
     * @throws UrlGenerationException if the route name is not known
     *                                or a parameter value does not match its regex
     */
    public function generateUri(string $routeName, array $parameters = [], array $queryParams = []): ?string;

    /**
     * Set the root controller namespace.
     *
     * @param string $rootNamespace
     *
     * @return $this
     */
    public function setNamespace(?string $rootNamespace = null): RouteCollectorInterface;

    /**
     * Get named route object
     *
     * @param string $name Route name
     *
     * @return RouteInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * Remove named route
     *
     * @param string $name Route name
     * @return RouteCollectorInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface;

    /**
     * Lookup a route via the route's unique identifier
     *
     * @param RouteInterface $route
     * @return void
     */
    public function addLookupRoute(RouteInterface $route): void;

    /**
     * Get the current route.
     *
     * @return RouteInterface|null
     */
    public function currentRoute(): ?RouteInterface;

    /**
     * Add this to keep the HTTP method when redirecting.
     *
     * redirections are temporary by default (code 302)
     *
     * @param bool $status
     * @return RouteCollectorInterface
     */
    public function keepRequestMethod(bool $status = false): RouteCollectorInterface;

    /**
     * Get current http request instance.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface;

    /**
     * Change the current request. Can be useful for
     * forward response.
     *
     * @param ServerRequestInterface $request
     * @return RouteCollectorInterface
     */
    public function setRequest(ServerRequestInterface $request): RouteCollectorInterface;

    /**
     * Ge the current router used.
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface;

    /**
     * Get the Middlewares Dispatcher
     *
     * @return MiddlewareDisptcher
     */
    public function getMiddlewareDispatcher(): MiddlewareDisptcher;

    /**
     * Set the global the middlewares stack attached to all routes.
     *
     * @param array|string|callable|MiddlewareInterface $middleware
     *
     * @return $this|array
     */
    public function addMiddlewares($middleware = []): RouteCollectorInterface;

    /**
     * Set the route middleware and call it as a method on route.
     *
     * @param array $middlewares [name => $middlewares ?? [$middlewares]]
     *
     * @return $this|array
     */
    public function routeMiddlewares($middlewares = []): RouteCollectorInterface;

    /**
     * Get all middlewares from stack
     *
     * @return array
     */
    public function getMiddlewaresStack(): array;

    /**
     * Adds parameters.
     *
     * This method implements a fluent interface.
     *
     * @param array $parameters The parameters
     * @param int $type
     *
     * @return $this
     */
    public function addParameters(array $parameters, int $type = self::TYPE_REQUIREMENT): RouteCollectorInterface;

    /**
     * Add route group
     *
     * @param array $attributes
     * @param string|callable $callable
     *
     * @return RouteGroupInterface
     */
    public function group(array $attributes, $callable): RouteGroupInterface;

    /**
     * Add route
     *
     * @param string[]                       $methods Array of HTTP methods
     * @param string                         $pattern The route pattern
     * @param callable|string|Closure|object $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler = null): RouteInterface;

    /**
     * Same as to map(); method.
     *
     * @param RouteInterface $route
     *
     * @return void
     */
    public function setRoute(RouteInterface $route): void;

    /**
     * Dispatches a matched route response.
     *
     * Uses the composed router to match against the incoming request, and
     * injects the request passed to the handler with the `RouteResulst` instance
     * returned (using the `RouteResults` class name as the attribute name).
     * If routing succeeds, injects the request passed to the handler with any
     * matched parameters as well.
     *
     * @return ResponseInterface
     *
     * @throws RouteNotFoundException
     * @throws ExceptionInterface
     */
    public function dispatch(): ResponseInterface;
}

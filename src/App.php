<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleMVC\Controller\AttributeInterface;
use SimpleMVC\Controller\Error404;
use SimpleMVC\Controller\Error405;
use SimpleMVC\Exception\ControllerException;
use SimpleMVC\Exception\InvalidConfigException;
use SimpleMVC\Response\HaltResponse;

use function FastRoute\cachedDispatcher;

class App
{
    const VERSION = '0.2.0';

    private Dispatcher $dispatcher;
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private float $startTime;
    
    /** @var mixed[] */
    private array $config;

    /**
     * @throws InvalidConfigException
     */
    public function __construct(ContainerInterface $container)
    {
        $this->startTime = microtime(true);
        $this->container = $container;    

        try {
            $this->config = $container->get('config');
        } catch (NotFoundExceptionInterface $e) {
            throw new InvalidConfigException(
                'The configuration is missing! Be sure to have a "config" key in the container'
            );
        }
        if (!isset($this->config['routing']['routes'])) {
            throw new InvalidConfigException(
                'The ["routing"]["routes"] is missing in configuration'
            );
        }
        $routes = $this->config['routing']['routes'];
        // Routing initialization
        $this->dispatcher = cachedDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        }, [
            'cacheFile'     => $this->config['routing']['cache'] ?? '',
            'cacheDisabled' => !isset($this->config['routing']['cache'])
        ]);

        // Logger initialization
        try {
            $this->logger = $container->get(LoggerInterface::class);
        } catch (NotFoundExceptionInterface $e) {
            $this->logger = new NullLogger();
        }

        if (isset($this->config['bootstrap']) && !is_callable($this->config['bootstrap'])) {
            throw new InvalidConfigException('The ["bootstrap"] must a callable');
        }
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }
    
    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function bootstrap(): void
    {
        if (isset($this->config['bootstrap'])) {
            $start = microtime(true);
            $this->config['bootstrap']($this->container);
            $this->logger->debug(sprintf("Bootstrap execution: %.3f sec", microtime(true) - $start));
        }
    }

    /**
     * @throws ControllerException
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info(sprintf(
            "Request: %s %s", 
            $request->getMethod(), 
            $request->getUri()->getPath()
        ));

        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(), 
            $request->getUri()->getPath()
        );
        $controllerName = null;
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $this->logger->warning('Controller not found (404)');
                $controllerName = $this->config['errors']['404'] ?? Error404::class;
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->logger->warning('Method not allowed (405)');
                $controllerName = $this->config['errors']['405'] ?? Error405::class;
                break;
            case Dispatcher::FOUND:
                $controllerName = $routeInfo[1];
                if (isset($routeInfo[2])) {
                    foreach ($routeInfo[2] as $name => $value) {
                        $request = $request->withAttribute($name, $value);
                    }
                }
                break;
        }
        // default HTTP response
        $response = new Response(200);

        if (!is_array($controllerName)) {
            $controllerName = [$controllerName];
        }
        foreach ($controllerName as $name) {
            $this->logger->debug(sprintf("Executing %s", $name));
            try {
                $controller = $this->container->get($name);
                $response = $controller->execute($request, $response);
                if ($response instanceof HaltResponse) {
                    $this->logger->debug(sprintf("Found HaltResponse in %s", $name));
                    break;
                }
                // Add the PSR-7 attributes to the next request
                if ($controller instanceof AttributeInterface) {
                    foreach ($controller->getRequestAttribute() as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                }
            } catch (NotFoundExceptionInterface $e) {
                throw new ControllerException(sprintf(
                    'The controller name %s cannot be retrieved from the container',
                    $name
                ));
            }    
        }

        $this->logger->info(sprintf(
            "Response: %d", 
            $response->getStatusCode()
        ));

        $this->logger->info(sprintf("Execution time: %.3f sec", microtime(true) - $this->startTime));
        $this->logger->info(sprintf("Memory usage: %d bytes", memory_get_usage(true)));

        return $response;
    }

    /**
     * Returns a PSR-7 request from globals ($_GET, $_POST, $_SERVER, etc)
     */
    public static function buildRequestFromGlobals(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        return (new ServerRequestCreator($factory, $factory, $factory, $factory))
            ->fromGlobals();
    }
}
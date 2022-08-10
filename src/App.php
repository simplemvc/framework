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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleMVC\Controller\Error404;
use SimpleMVC\Controller\Error405;
use SimpleMVC\Exception\ControllerException;
use SimpleMVC\Exception\InvalidConfigException;
use SimpleMVC\Response\HaltResponse;

use function FastRoute\cachedDispatcher;

class App
{
    const VERSION = '0.1.1';

    private Dispatcher $dispatcher;
    private ServerRequestInterface $request;
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private float $startTime;
    /** @var mixed[] */
    private array $config;

    public function __construct(ContainerInterface $container)
    {
        $this->startTime = microtime(true);
        $this->container = $container;    

        try {
            $this->config = $container->get('config');
        } catch (NotFoundExceptionInterface $e) {
            throw new InvalidConfigException(
                'The configuation is missing! Be sure to have a "config" key in the container'
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

        // Build the PSR-7 request
        $f = new Psr17Factory();
        $this->request = (new ServerRequestCreator($f, $f, $f, $f))->fromGlobals();
        
        $this->logger->info(sprintf(
            "Request: %s %s", 
            $this->request->getMethod(), 
            $this->request->getUri()->getPath()
        ));
    }

    /**
     * Returns the PSR-7 request
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
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
            $this->logger->info(sprintf("Bootstrap execution: %.3f sec", microtime(true) - $start));
        }
    }

    public function dispatch(): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch(
            $this->request->getMethod(), 
            $this->request->getUri()->getPath()
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
                        $this->request = $this->request->withAttribute($name, $value);
                    }
                }
                break;
        }
        // default HTTP response
        $response = new Response(200);

        if (is_string($controllerName)) {
            $this->logger->info(sprintf("Executing %s", $controllerName));
            try {
                $controller = $this->container->get($controllerName);
                $response = $controller->execute($this->request, $response);  
            } catch (NotFoundExceptionInterface $e) {
                throw new ControllerException(sprintf(
                    'The controller name %s cannot be retrieved from the container',
                    $controllerName
                ));
            }  
        } elseif (is_array($controllerName)) {
            foreach ($controllerName as $controller) {
                $this->logger->info(sprintf("Executing %s", $controller));
                try {
                    $response = $this->container
                        ->get($controller)
                        ->execute($this->request, $response);
                    if ($response instanceof HaltResponse) {
                        $this->logger->info(sprintf("Found HaltResponse in %s", $controller));
                        break;
                    }
                } catch (NotFoundExceptionInterface $e) {
                    throw new ControllerException(sprintf(
                        'The controller name %s cannot be retrieved from the container',
                        $controller
                    ));
                }    
            }
        } else {
            throw new ControllerException(sprintf(
                'The controller name %s must be a string or array',
                var_export($controllerName, true)
            ));
        }
        
        $this->logger->info(sprintf("Execution time: %.3f sec", microtime(true) - $this->startTime));
        $this->logger->info(sprintf("Memory usage: %d bytes", memory_get_usage(true)));

        return $response;
    }
}
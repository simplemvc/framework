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
    const VERSION = '0.1';

    private Dispatcher $dispatcher;
    private ServerRequestInterface $request;
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private float $startTime;

    /**
     * @var mixed[]
     */
    private $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(ContainerInterface $container, array $config)
    {
        $this->startTime = microtime(true);
        $this->container = $container;
        $this->config    = $config;        

        // Routing initialization
        if (!isset($config['routing']['routes'])) {
            throw new InvalidConfigException(
                'You need to provide a [\'routing\'][\'routes\'] value in configuration'
            );
        }
        $this->dispatcher = cachedDispatcher(function(RouteCollector $r) use ($config) {
            foreach ($config['routing']['routes'] as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        }, [
            'cacheFile'     => $config['routing']['cache'] ?? '',
            'cacheDisabled' => !isset($config['routing']['cache'])
        ]);

        // Logger initialization
        if (isset($config['logger']) && !($config['logger'] instanceof LoggerInterface)) {
            throw new InvalidConfigException(sprintf(
                "The logger must implement %s'",
                LoggerInterface::class
            ));
        }
        $this->logger = $config['logger'] ?? new NullLogger();

        $f = new Psr17Factory();
        $this->request = (new ServerRequestCreator($f, $f, $f, $f))->fromGlobals();
        
        $this->logger->info(sprintf(
            "Request: %s %s", 
            $this->request->getMethod(), 
            $this->request->getUri()->getPath()
        ));
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function bootstrap(): void
    {
        // Initialize custom bootstrap, if any
        $bootstrap = $this->config['bootstrap'] ?? null;
        if (null !== $bootstrap && !is_callable($bootstrap)) {
            throw new InvalidConfigException('The bootstrap config value must be a callable!');
        }
        if (null !== $bootstrap) {
            $start = microtime(true);
            $bootstrap($this->container);
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
                $controllerName = $this->config['error']['404'] ?? Error404::class;
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->logger->warning('Method not allowed (405)');
                $controllerName = $this->config['error']['405'] ?? Error405::class;
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
            $controller = $this->container->get($controllerName);
            if (empty($controller)) {
                throw new ControllerException(sprintf(
                    'The controller name %s cannot be retrieved from the container',
                    $controllerName
                ));
            }
            $response = $controller->execute($this->request, $response);    
        } elseif (is_array($controllerName)) {
            foreach ($controllerName as $controller) {
                $this->logger->info(sprintf("Executing %s", $controller));
                $response = $this->container
                    ->get($controller)
                    ->execute($this->request, $response);
                if ($response instanceof HaltResponse) {
                    $this->logger->info(sprintf("Found HaltResponse in %s", $controller));
                    break;
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
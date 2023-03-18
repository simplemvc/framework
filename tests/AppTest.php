<?php

/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Test;

use Exception;
use FastRoute\Dispatcher;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SimpleMVC\App;
use SimpleMVC\Controller\BasicAuth;
use SimpleMVC\Controller\Error404;
use SimpleMVC\Exception\ControllerException;
use SimpleMVC\Exception\InvalidConfigException;
use SimpleMVC\Response\HaltResponse;
use SimpleMVC\Test\Asset\TestAttributeController;
use SimpleMVC\Test\Asset\TestController;

class AppTest extends TestCase
{
    /** @var ContainerInterface|MockInterface|LegacyMockInterface */
    private $container;

    private string $tmpCacheFile;

    /** @var mixed[] */
    private array $config;

    private ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->tmpCacheFile = sys_get_temp_dir() . '/cache.route';
        $this->config = [
            'routing' => [
                'routes' => []
            ]
        ];

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config)
            ->byDefault();

        $this->container->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andThrow(
                new class extends Exception implements NotFoundExceptionInterface {
                }
            )
            ->byDefault();

        $this->container->shouldReceive('get')
            ->with(Error404::class)
            ->andReturn(new Error404)
            ->byDefault();

        $this->container->shouldReceive('get')
            ->with(TestController::class)
            ->andReturn(new TestController)
            ->byDefault();

        $this->request = new ServerRequest('GET', '/');
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpCacheFile)) {
            unlink($this->tmpCacheFile);
        }
    }

    public function testGetContainer(): void
    {
        $app = new App($this->container);
        $this->assertInstanceOf(ContainerInterface::class, $app->getContainer());
        $this->assertEquals($this->container, $app->getContainer());
    }

    public function testGetLogger(): void
    {
        $app = new App($this->container);
        $this->assertInstanceOf(LoggerInterface::class, $app->getLogger());
    }

    public function testGetDefaultNullLogger(): void
    {
        $app = new App($this->container);
        $this->assertInstanceOf(NullLogger::class, $app->getLogger());
    }

    public function testUseCustomLogger(): void
    {
        $customLogger = new class extends NullLogger {
        };
        $this->container->shouldReceive('get')
            ->with(LoggerInterface::class)
            ->andReturn($customLogger);

        $app = new App($this->container);
        $this->assertEquals($customLogger, $app->getLogger());
    }

    public function testGetConfig(): void
    {
        $app = new App($this->container);
        $this->assertEquals($this->config, $app->getConfig());
    }

    public function testGetDispatcher(): void
    {
        $app = new App($this->container);
        $this->assertInstanceOf(Dispatcher::class, $app->getDispatcher());
    }

    public function testEnableCacheForRouting(): void
    {
        $this->config['routing']['cache'] = $this->tmpCacheFile;
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $app = new App($this->container);
        $this->assertFileExists($this->tmpCacheFile);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testBootstrapWithEmptyValue(): void
    {
        $app = new App($this->container);
        $app->bootstrap();
    }

    public function testBootstrapWithClosure(): void
    {
        $this->config['bootstrap'] = function (ContainerInterface $c) {
            $this->assertInstanceOf(ContainerInterface::class, $c);
        };
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $app = new App($this->container);
        $app->bootstrap();
    }

    public function testBootstrapWithNoCallable(): void
    {
        $this->config['bootstrap'] = 'test';
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $this->expectException(InvalidConfigException::class);
        $app = new App($this->container);
    }

    public function testDispatchWithoutRouteReturns404(): void
    {
        $app = new App($this->container);
        $response = $app->dispatch($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchWithRouteAndControllerReturns200(): void
    {
        $this->config = [
            'routing' => [
                'routes' => [
                    ['GET', '/', TestController::class]
                ]
            ]
        ];
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $app = new App($this->container);
        $response = $app->dispatch($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchControllerPipelineWithBasicAuthReturns401(): void
    {
        $this->config = [
            'routing' => [
                'routes' => [
                    ['GET', '/', [BasicAuth::class, TestController::class]]
                ]
            ],
            'authentication' => [
                'username' => 'test',
                'password' => 'password'
            ]
        ];
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $this->container->shouldReceive('get')
            ->with(BasicAuth::class)
            ->andReturn(new BasicAuth($this->container));

        $app = new App($this->container);
        $this->request = $this->request->withHeader(
            'Authorization',
            sprintf("Basic %s", base64_encode('test:password'))
        );
        $response = $app->dispatch($this->request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchControllerPipelineWithBasicAuthReturns200(): void
    {
        $this->config = [
            'routing' => [
                'routes' => [
                    ['GET', '/', [BasicAuth::class, TestController::class]]
                ]
            ],
            'authentication' => [
                'username' => 'test',
                'password' => 'password'
            ]
        ];
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $this->container->shouldReceive('get')
            ->with(BasicAuth::class)
            ->andReturn(new BasicAuth($this->container));

        $app = new App($this->container);
        $response = $app->dispatch($this->request);
        $this->assertInstanceOf(HaltResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testDispatchControllerPipelineWithAttribute(): void
    {
        $attributes = ['foo' => 'bar'];
        $this->config = [
            'routing' => [
                'routes' => [
                    ['GET', '/', [TestAttributeController::class, TestController::class]]
                ]
            ]
        ];
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn($this->config);

        $this->container->shouldReceive('get')
            ->with(TestAttributeController::class)
            ->andReturn(new TestAttributeController($attributes));

        $controller = new TestController();
        $this->container->shouldReceive('get')
            ->with(TestController::class)
            ->andReturn($controller);

        $app = new App($this->container);
        $response = $app->dispatch($this->request);
        $this->assertEquals($attributes, $controller->attributes);
    }

    public function testBuildRequestFromGlobals(): void
    {
        $this->assertInstanceOf(ServerRequestInterface::class, App::buildRequestFromGlobals());
    }

    public function testCannotGetControllerFromContainerException(): void
    {
        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage(
            'Cannot get controller from container for route [ GET / ]: This error being throw by a test'
        );
        $this->expectExceptionCode(0);

        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn(['routing' => ['routes' => [['GET', '/', TestController::class]]]]);
        $this->container->shouldReceive('get')
            ->with(TestController::class)
            ->andThrow(
                new class('This error being throw by a test') extends RuntimeException implements
                    NotFoundExceptionInterface {
                }
            );

        $app = new App($this->container);
        $app->dispatch($this->request);
    }
}

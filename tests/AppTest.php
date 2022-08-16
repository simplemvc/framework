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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleMVC\App;
use SimpleMVC\Controller\Error404;
use SimpleMVC\Exception\InvalidConfigException;

class AppTest extends TestCase
{   
    /** @var ContainerInterface|MockObject */
    private $container;

    private string $tmpCacheFile;

    /** @var mixed[] */
    private array $config;

    private AnyInvokedCount $matcher;

    public function setUp(): void
    {
        $this->tmpCacheFile = sys_get_temp_dir() . '/cache.route';
        $this->config = [ 
            'routing' => [
                'routes' => []
            ]
        ];
        $this->container = $this->createMock(ContainerInterface::class);
        $this->matcher = $this->any();
        $this->container
            ->expects($this->matcher)
            ->method('get')
            ->withConsecutive(['config'], [LoggerInterface::class])
            ->willReturnCallback(function() {
                switch($this->matcher->getInvocationCount()) {
                    case 1:
                        return $this->config;
                    case 2:
                        throw new class extends Exception implements NotFoundExceptionInterface {};
                    case 3:
                        return new Error404();
                }
            });
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
        $customLogger = new class extends NullLogger {};
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->matcher)
            ->method('get')
            ->withConsecutive(['config'], [LoggerInterface::class])
            ->willReturnCallback(function() use ($customLogger) {
                switch($this->matcher->getInvocationCount()) {
                    case 1:
                        return $this->config;
                    case 2:
                        return $customLogger;
                }
        });
        $app = new App($container);
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
        $app = new App($this->container);
        $this->assertFileExists($this->tmpCacheFile);
    }

    public function testGetRequest(): void
    {
        $app = new App($this->container);
        $this->assertInstanceOf(RequestInterface::class, $app->getRequest());
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
        $this->config['bootstrap'] = function(ContainerInterface $c) {
            $this->assertInstanceOf(ContainerInterface::class, $c);
        };
        $app = new App($this->container);
        $app->bootstrap();
    }

    public function testBootstrapWithNoCallable(): void
    {
        $this->config['bootstrap'] = 'test';
        $this->expectException(InvalidConfigException::class);
        $app = new App($this->container);
    }

    public function testDispatch(): void
    {
        $app = new App($this->container);
        $response = $app->dispatch();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());            
    }
}
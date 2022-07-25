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

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleMVC\App;
use SimpleMVC\Controller\Error404;
use SimpleMVC\Exception\InvalidConfigException;

class AppTest extends TestCase
{
    private ContainerInterface $container;
    
    private App $app;

    /** @var mixed */
    private array $config;

    public function setUp(): void
    {
        $this->config = [ 
            'routing' => [
                'routes' => []
            ]
        ];
        $this->container = $this->createMock(ContainerInterface::class);
        $this->app = new App($this->container, $this->config);
    }

    public function testGetContainer(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->app->getContainer());
    }

    public function testGetConfig(): void
    {
        $this->assertEquals($this->config, $this->app->getConfig());
    }

    public function testGetLogger(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->app->getLogger());
    }

    public function testGetRequest(): void
    {
        $this->assertInstanceOf(RequestInterface::class, $this->app->getRequest());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testBootstrapWithEmptyValue(): void
    {
        $this->app->bootstrap();
    }

    public function testBootstrapWithClosure(): void
    {
        $this->config['bootstrap'] = function(ContainerInterface $c) {
            $this->assertInstanceOf(ContainerInterface::class, $c);
        };
        $this->app = new App($this->container, $this->config);
        $this->app->bootstrap();
    }

    public function testBootstrapWithNoCallable(): void
    {
        $this->config['bootstrap'] = 'test';
        $this->app = new App($this->container, $this->config);
        $this->expectException(InvalidConfigException::class);
        $this->app->bootstrap();
    }

    public function testDispatch(): void
    {
        $this->container->method('get')
            ->with($this->equalTo('SimpleMVC\Controller\Error404'))
            ->willReturn(new Error404);

        $response = $this->app->dispatch();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());            
    }
}
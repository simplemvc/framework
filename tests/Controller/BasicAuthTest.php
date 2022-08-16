<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Test\Controller;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleMVC\Controller\ControllerInterface;
use SimpleMVC\Controller\BasicAuth;

final class BasicAuthTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    private ControllerInterface $auth;
    
    /** @var ServerRequestInterface&MockObject */
    private $request;

    private ResponseInterface $response;

    /** @var mixed[] */
    private $config;

    public function setUp(): void
    {
        $this->config = [
            'authentication' => [
                'username' => 'test',
                'password' => 'password'
            ]
        ];
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')
            ->with($this->equalTo('config'))
            ->willReturn($this->config);

        $this->auth = new BasicAuth($this->container);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = new Response(200);
    }

    public function testAuthWithValidCredentials(): void
    {
        $this->request->method('getHeader')
            ->with($this->equalTo('Authorization'))
            ->willReturn([sprintf(
                'Basic %s',
                base64_encode($this->config['authentication']['username'] . ':' . $this->config['authentication']['password'])
            )]);

        $response = $this->auth->execute($this->request, $this->response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAuthWithInvalidCredentials(): void
    {
        $this->request->method('getHeader')
            ->with($this->equalTo('Authorization'))
            ->willReturn([sprintf(
                'Basic %s',
                base64_encode('foo:bar')
            )]);

        $response = $this->auth->execute($this->request, $this->response);

        $this->assertNotEmpty($response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm=""', $response->getHeader('WWW-Authenticate')[0]);
    }

    public function testAuthWithoutCredentials(): void
    {
        $response = $this->auth->execute($this->request, $this->response);
        
        $this->assertNotEmpty($response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm=""', $response->getHeader('WWW-Authenticate')[0]);
    }
}

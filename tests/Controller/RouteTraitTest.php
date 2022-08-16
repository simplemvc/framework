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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleMVC\Controller\RouteTrait;

final class RouteTraitTest extends TestCase
{
    use RouteTrait;

    /** @var ServerRequestInterface&MockObject */
    private $request;

    private ResponseInterface $response;

    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = new Response(200);
    }

    /**
     * @return mixed[]
     */
    public function getHttpMethods(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['HEAD']
        ];
    }

    /**
     * @dataProvider getHttpMethods
     */
    public function testExecute(string $method): void
    {
        $this->request
            ->method('getMethod')
            ->willReturn($method);

        $response = $this->execute($this->request, $this->response);
        $this->assertEquals($this->response, $response);
    }

    public function testOptionsMethodReturns405(): void
    {
        $this->request
            ->method('getMethod')
            ->willReturn('OPTIONS');

        $response = $this->execute($this->request, $this->response);
        $this->assertEquals(405, $response->getStatusCode());
    }

    protected function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    protected function post(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    protected function put(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    protected function patch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    protected function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    protected function head(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
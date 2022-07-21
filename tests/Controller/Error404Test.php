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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleMVC\Controller\ControllerInterface;
use SimpleMVC\Controller\Error404;

final class Error404Test extends TestCase
{
    /** @var ControllerInterface */
    private $error;
    
    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var ResponseInterface */
    private $response;

    public function setUp(): void
    {
        $this->error = new Error404();
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testExecuteRender404View(): void
    {
        $response = $this->error->execute($this->request, $this->response);
        $this->assertEquals(404, $response->getStatusCode());
    }
}

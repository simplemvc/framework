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
use SimpleMVC\Controller\Error405;

final class Error405Test extends TestCase
{
    /** @var ControllerInterface */
    private $error;
    
    /** @var ServerRequestInterface&MockObject */
    private $request;
    
    /** @var ResponseInterface */
    private $response;

    public function setUp(): void
    {
        $this->error = new Error405();
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testExecuteRender405View(): void
    {
        $response = $this->error->execute($this->request, $this->response);
        $this->assertEquals(405, $response->getStatusCode());
    }
}

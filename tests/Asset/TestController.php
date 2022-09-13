<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Test\Asset;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleMVC\Controller\ControllerInterface;

class TestController implements ControllerInterface
{
    /**
     * @var mixed[]
     */
    public array $attributes;

    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->attributes = $request->getAttributes();
        return $response;
    }
}
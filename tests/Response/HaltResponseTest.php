<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Test\Response;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use SimpleMVC\Response\HaltResponse;

class HaltResponseTest extends TestCase
{
    public function testHaltIsPsr7Response()
    {
        $halt = new HaltResponse();
        $this->assertInstanceOf(ResponseInterface::class, $halt);
    }
}
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

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SimpleMVC\Emitter\SapiEmitter;

class SapiEmitterTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testEmitWith200Response(): void
    {
        $response = new Response(200);
        SapiEmitter::emit($response);

        $this->assertEquals(200, http_response_code());
    }

    /**
     * @runInSeparateProcess
     */
    public function testEmitWith400Response(): void
    {
        $response = new Response(400);
        SapiEmitter::emit($response);

        $this->assertEquals(400, http_response_code());
    }
}
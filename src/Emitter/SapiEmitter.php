<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Emitter;

use Psr\Http\Message\ResponseInterface;
use SimpleMVC\App;

class SapiEmitter implements EmitterInterface
{
    public static function emit(ResponseInterface $response): string
    {
        // status code line
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            empty($reasonPhrase) ? '' : ' ' . $reasonPhrase
        ), true, $statusCode);

        // headers
        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Set the SimpleMVC Server header
        header(sprintf("Server: SimpleMVC %s/PHP %s", App::VERSION, phpversion()));

        // body
        $body = (string) $response->getBody();
        if (!empty($body)) {
            header(sprintf("Content-Length: %d", strlen($body)));
        }
        return $body;
    }
}
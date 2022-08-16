<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleMVC\Exception\InvalidConfigException;
use SimpleMVC\Response\HaltResponse;

class BasicAuth implements ControllerInterface
{
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $realm;

    /**
     * @throws InvalidConfigException
     */
    public function __construct(ContainerInterface $container)
    {
        $config = $container->get('config');
        if (!isset($config['authentication'])) {
            throw new InvalidConfigException(
                'The ["config"]["authentication"] is missing in configuration'
            );
        }
        $this->username = $config['authentication']['username'] ?? null;
        $this->password = $config['authentication']['password'] ?? null;
        $this->realm    = $config['authentication']['realm'] ?? '';

        if (is_null($this->username) || is_null($this->password)) {
            throw new InvalidConfigException(
                'Username and password are missing in ["config"]["authentication"]'
            );
        }
    }

    /**
     * Implements Basic Access Authentication
     * 
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getHeader('Authorization');
        if (empty($auth)) {
            return new HaltResponse(401, ['WWW-Authenticate' => "Basic realm=\"{$this->realm}\""]);
        }
        list (, $credential) = explode('Basic ', $auth[0]);
        list($username, $password) = explode(':', base64_decode($credential));
        if ($username !== $this->username || $password !== $this->password) {
            return new HaltResponse(401, ['WWW-Authenticate' => "Basic realm=\"{$this->realm}\""]);
        }
        return $response;
    }
}
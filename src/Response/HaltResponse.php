<?php
/**
 * SimpleMVC
 *
 * @link      http://github.com/simplemvc/framework
 * @copyright Copyright (c) Enrico Zimuel (https://www.zimuel.it)
 * @license   https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace SimpleMVC\Response;

use Nyholm\Psr7\Response;

/**
 * This is an empty class that extends Nyholm\Psr7\Response
 * used to halt the execution flow of SimpleMVC
 */
class HaltResponse extends Response
{

}
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

interface AttributeInterface
{
    /**
     * Add an attribute for the next PSR-7 request in a pipeline
     * 
     * @param mixed $value
     */
    public function addRequestAttribute(string $name, $value): void;

    /**
     * Get a request attribute, if $name is not specified returns
     * all the attributes as array
     * 
     * @return mixed
     */
    public function getRequestAttribute(string $name = null);
}
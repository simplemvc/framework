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

trait AttributeTrait
{
    /** @var mixed[] */
    protected array $attributes = [];

    /**
     * @param mixed $value
     */
    public function addRequestAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @return mixed
     */
    public function getRequestAttribute(string $name = null)
    {
        if (empty($name)) {
            return $this->attributes;
        }
        return $this->attributes[$name] ?? null;
    }
}
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

use PHPUnit\Framework\TestCase;
use SimpleMVC\Controller\AttributeInterface;
use SimpleMVC\Controller\AttributeTrait;

final class AttributeTraitTest extends TestCase implements AttributeInterface
{
    use AttributeTrait;

    public function testAddAttribute(): void
    {
        $this->addRequestAttribute('foo', 'bar');
        $this->assertEquals('bar', $this->getRequestAttribute('foo'));
        $this->assertEquals(['foo' => 'bar'], $this->getRequestAttribute());
    }

    public function testAddTwoAttributes(): void
    {
        $this->addRequestAttribute('foo', 'bar');
        $this->addRequestAttribute('baz', 'boo');
        $this->assertEquals('bar', $this->getRequestAttribute('foo'));
        $this->assertEquals('boo', $this->getRequestAttribute('baz'));
        $this->assertEquals(['foo' => 'bar', 'baz' => 'boo'], $this->getRequestAttribute());
    }

    public function testGetAttributeReturnsEmptyArray(): void
    {
        $this->assertEquals([], $this->getRequestAttribute());
    }

    public function testGetAttributeWithUnknownKeyReturnsNull(): void
    {
        $this->assertNull($this->getRequestAttribute('foo'));
    }
}
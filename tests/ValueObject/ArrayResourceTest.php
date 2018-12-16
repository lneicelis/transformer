<?php

namespace Lneicelis\Transformer\ValueObject;

use Lneicelis\Transformer\Stub\CategoryResource;
use PHPUnit\Framework\TestCase;

class ArrayResourceTest extends TestCase
{
    /** @test */
    public function itAllowsToAccessProperties(): void
    {
        $proxy = new CategoryResource([
            'name' => 'category name',
        ]);

        $this->assertEquals('category name', $proxy->name);
    }

    /** @test */
    public function itAllowsToAccessKey(): void
    {
        $proxy = new CategoryResource([
            'name' => 'category name',
        ]);

        $this->assertEquals('category name', $proxy['name']);
    }

    /** @test */
    public function itCanBeAccessedObjectLikeIndefinitelyWithoutBreaking(): void
    {
        $proxy = new CategoryResource([]);
        $value = $proxy->a->b->c->e->f;

        $this->assertEquals(NullProxy::instance(), $value);
    }

    /** @test */
    public function itCanBeAccessedArrayLikeIndefinitelyWithoutBreaking(): void
    {
        $proxy = new CategoryResource([]);
        $value = $proxy['a']['b']['c']['d'];

        $this->assertEquals(NullProxy::instance(), $value);
    }
}

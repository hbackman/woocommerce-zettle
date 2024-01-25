<?php
namespace Zettle\Test\Support;

use PHPUnit\Framework\TestCase;
use Zettle\Support\Arr;

class ArrTest extends TestCase
{
    public function testGet()
    {
        // Simple get.
        $data = [
            "foo" => "bar",
            "bar" => "baz",
        ];

        $this->assertEquals("bar", Arr::get($data, "foo"));

        // Simple nested.
        $data = [
            "foo" => [
                "bar" => "baz",
            ],
        ];

        $this->assertEquals("baz", Arr::get($data, "foo.bar"));

        // Wildcard nested.
        $data = [
            "foo" => [
                ["key" => "v1"],
                ["key" => "v2"],
            ],
        ];

        $this->assertEquals([
            "v1",
            "v2",
        ], Arr::get($data, "foo.*.key"));

        // Default value.
        $this->assertEquals(null, Arr::get([], "foo"));
        $this->assertEquals("df", Arr::get([], "foo", "df"));
    }

    public function testLen()
    {
        // Length without key.
        $this->assertEquals(3, Arr::len([
            1, 2, 3,
        ]));

        // Length with key.
        $this->assertEquals(3, Arr::len([
            "foo" => [1, 2, 3],
            "bar" => [1, 2],
        ], "foo"));
    }

    public function testHas()
    {
        $data = [
            "foo" => "bar",
        ];

        $this->assertTrue (Arr::has($data, "foo"));
        $this->assertFalse(Arr::has($data, "bar"));
    }

    public function testMap()
    {
        $data = [
            "A" => "1",
            "B" => "2",
            "C" => "3",
        ];

        $ret = Arr::map($data, function ($v, $k) {
            return [$v, $k];
        });

        $this->assertContains(["1", "A"], $ret);
        $this->assertContains(["2", "B"], $ret);
        $this->assertContains(["3", "C"], $ret);
    }

    public function testPluck()
    {
        $data = [
            ["id" => 1, "name" => "Foo"],
            ["id" => 2, "name" => "Bar"],
        ];

        $ret = Arr::pluck($data, "name");

        $this->assertCount(2, $ret);
        $this->assertContains("Foo", $ret);
        $this->assertContains("Bar", $ret);
    }
}
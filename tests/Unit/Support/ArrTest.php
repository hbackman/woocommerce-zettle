<?php
namespace Zettle\Test\Unit\Support;

use Zettle\Support\Arr;
use Zettle\Test\TestCase;

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

    public function testKeyBy()
    {
        $data = [
            ["id" => 1, "first_name" => "Joe"],
            ["id" => 3, "first_name" => "Kamal"],
        ];

        $ret = Arr::keyBy($data, "id");

        $this->assertEquals("Joe",   $ret[1]["first_name"]);
        $this->assertEquals("Kamal", $ret[3]["first_name"]);
    }

    public function testKeyByWithCallable()
    {
        $data = [
            ["post_id" => 1, "post_content" => "Lorem ipsum dolor sit amet."],
            ["post_id" => 2, "post_content" => "Sed sit amet posuere tortor."],
        ];

        $ret = Arr::keyBy($data, fn ($item) => md5($item["post_content"]));

        $this->assertArrayHasKey(md5("Lorem ipsum dolor sit amet."),  $ret);
        $this->assertArrayHasKey(md5("Sed sit amet posuere tortor."), $ret);
    }

    public function testFlatten()
    {
        $data = [
            1, [2, [3, [4, [5]]]],
        ];

        $this->assertEquals([1, 2, 3, 4, 5], Arr::flatten($data));
        $this->assertEquals([1, 2, 3, 4, [5]], Arr::flatten($data, 3));
    }

    public function testWhere()
    {
        // There could definitely be more coverage here. But idk, I dont want to do it.

        $data = [
            ["post_id" => 1, "post_type" => "post"],
            ["post_id" => 2, "post_type" => "attachment"],
            ["post_id" => 3, "post_type" => "attachment"],
        ];

        $this->assertCount(1, Arr::where($data, "post_type", "=", "post"));
        $this->assertCount(2, Arr::where($data, "post_type", "=", "attachment"));
    }

    public function testFirst()
    {
        $data = [
            ["post_id" => 1, "post_type" => "post"],
            ["post_id" => 2, "post_type" => "attachment"],
        ];

        $this->assertEquals($data[1], Arr::first($data, "post_id", "=", 2));
        $this->assertEquals(null,     Arr::first($data, "post_id", "=", 4));
    }

    public function testContains()
    {
        $data = [
            "foo",
            "bar",
        ];

        $this->assertTrue(Arr::contains($data, "foo"));
        $this->assertTrue(Arr::contains($data, "bar"));
        $this->assertFalse(Arr::contains($data, "baz"));
    }
}
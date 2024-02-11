<?php
namespace Zettle\Test\Unit\Webhook;

use Zettle\Test\TestCase;
use Zettle\Webhook\Request;

class RequestTest extends TestCase
{
    public function testBody()
    {
        $request = new Request([], $data = '{"hello": "world"}');

        $this->assertEquals($data, $request->body());
    }

    public function testJson()
    {
        $content = <<<JSON
{ "hello": ["world"] }
JSON;
        $request = new Request([], $content);

        $this->assertArrayHasKey("hello", $request->json());
        $this->assertContains("world", $request->json()["hello"]);
    }

    public function testHeader()
    {
        $request = new Request([
            "Accept" => "application/json",
            "Referer" => "PHP TEST CASE",
        ]);

        $this->assertEquals("application/json", $request->header("Accept"));
        $this->assertEquals("PHP TEST CASE",    $request->header("Referer"));
    }

    public function testMake()
    {
        // Setup request info.
        file_put_contents('php://input', '{"hello": "world"}');

        // Setup basic headers.
        $_SERVER["HTTP_ACCEPT"] = "application/json";
        $_SERVER["HTTP_REFERER"] = "PHP TEST CASE";

        // Setup specially formatted headers.
        $_SERVER["CONTENT_TYPE"] = "application/json";
        $_SERVER["CONTENT_LENGTH"] = "500";

        // Assert that those parts make it into the object.

        $request = Request::make();

        $this->assertEquals($_SERVER["HTTP_ACCEPT"],    $request->header("Accept"));
        $this->assertEquals($_SERVER["HTTP_REFERER"],   $request->header("Referer"));

        $this->assertEquals($_SERVER["CONTENT_TYPE"],   $request->header("Content-Type"));
        $this->assertEquals($_SERVER["CONTENT_LENGTH"], $request->header("Content-Length"));
    }
}
<?php
namespace Zettle\Test\Unit\Support;

use Requests_Response;
use WP_HTTP_Requests_Response;
use Zettle\Support\JsonResponse;
use Zettle\Test\TestCase;

class JsonResponseTest extends TestCase
{
    private function createResponse(?string $data = null)
    {
        $response = new Requests_Response();
        $response->body = $data;

        return JsonResponse::create(new WP_HTTP_Requests_Response($response));
    }

    public function testJsonWithPath()
    {
        $response = $this->createResponse('{
            "foo": "bar",
            "bar": {
                "key": "baz"
            }
        }');

        $this->assertEquals("bar", $response->json("foo"));
        $this->assertEquals("baz", $response->json("bar.key"));
    }

    public function testJsonWithoutPath()
    {
        $response = $this->createResponse("[
            1, 2, 3
        ]");

        $this->assertEquals([1, 2, 3], $response->json());
    }

    public function testEmptyBody()
    {
        $response = $this->createResponse(null);

        $this->assertNull($response->json());
        $this->assertNull($response->json("test"));
    }

    public function testSetData()
    {
        $response = $this->createResponse("[1]");

        $this->assertEquals([1], $response->json());

        $response->set_data("[2]");

        $this->assertEquals([2], $response->json());
    }

    public function testIsSuccessful()
    {
        foreach ([
            100 => false,
            200 => true,
            201 => true,
            204 => true,
            400 => false,
            404 => false,
            422 => false,
            500 => false,
        ] as $code => $successful) {
            $r = $this->createResponse();
            $r->set_status($code);

            $this->assertEquals($successful, $r->is_successful());
        }
    }

    public function testGetLink()
    {
        $r = $this->createResponse();
        $r->set_headers([
            "Link" =>
                '<https://zettle/products?page=2>; rel="prev",'.
                '<https://zettle/products?page=4>; rel="next",'.
                '<https://zettle/products?page=1>; rel="first"',
        ]);

        $this->assertEquals('https://zettle/products?page=2', $r->get_link("prev"));
        $this->assertEquals('https://zettle/products?page=4', $r->get_link("next"));
        $this->assertEquals('https://zettle/products?page=1', $r->get_link("first"));
    }
}
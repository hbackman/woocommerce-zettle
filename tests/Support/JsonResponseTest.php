<?php
namespace Zettle\Test\Support;

use WP_HTTP_Requests_Response;
use Requests_Response;

use PHPUnit\Framework\TestCase;
use Zettle\Support\JsonResponse;

class JsonResponseTest extends TestCase
{
    private function createResponse(?string $data)
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
}
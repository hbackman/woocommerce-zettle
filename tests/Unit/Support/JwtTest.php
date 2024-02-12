<?php
namespace Zettle\Test\Unit\Support;

use InvalidArgumentException;
use Zettle\Support\Jwt;
use Zettle\Test\TestCase;

class JwtTest extends TestCase
{
    private array $exampleToken = [
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.".
        "eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJleHAiOiIxNjc0NjE3MTc0In0.".
        "SflKxwRJSMeKKF2QT4fwpMeJf36POk6v2gYnazYa0xlZvWMLi-2SxrGKd_7MNvBUjy3z4rQgg4",
        [
            "alg" => "HS256",
            "typ" => "JWT",
        ],
        [
            "name" => "John Doe",
            "sub"  => "1234567890",
            "exp"  => "1674617174",
            "iat"  => "1516239022",
        ],
    ];

    public function testNew()
    {
        $jwt = new Jwt(
            $h = [
                "typ" => "JWT"
            ],
            $p = [
                "exp" => time(),
                "sub" => uniqid(),
                "iss" => "test",
            ],
            $s = "SIG",
            $t = "TOK",
        );

        $this->assertEquals($h, $jwt->getHeader());
        $this->assertEquals($p, $jwt->getPayload());
        $this->assertEquals($s, $jwt->getSignature());
        $this->assertEquals($t, $jwt->getToken());
    }

    public function testIsExpired()
    {
        // Test expired
        $token =
            base64_encode(json_encode(["typ" => "JWT"])).".".
            base64_encode(json_encode(["exp" => time() - 100])).".";

        $this->assertTrue(Jwt::parse($token)->isExpired());

        // Test Valid
        $token =
            base64_encode(json_encode(["typ" => "JWT"])).".".
            base64_encode(json_encode(["exp" => time() + 100])).".";

        $this->assertFalse(Jwt::parse($token)->isExpired());
    }

    public function testParse()
    {
        [$t, $h, $p] = $this->exampleToken;

        $jwt = Jwt::parse($t);

        $this->assertEquals($h, $jwt->getHeader());
        $this->assertEquals($p, $jwt->getPayload());
    }

    public function testParseInvalid()
    {
        try {
            Jwt::parse("");

            $this->fail("JWT::parse accepted an invalid token.");
        }
        catch (InvalidArgumentException $e) {
            $this->assertEquals("Token does not have 3 sections.", $e->getMessage());
        }

        try {
            Jwt::parse(null);

            $this->fail("JWT::parse accepted a null token.");
        }
        catch (InvalidArgumentException $e) {
            $this->assertEquals("The token cannot be empty.", $e->getMessage());
        }
    }
}
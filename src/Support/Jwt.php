<?php
namespace Zettle\Support;

use Webmozart\Assert\Assert;

class Jwt
{
    /**
     * The full jwt token.
     */
    private string $token;

    /**
     * The token header.
     */
    private array  $header;

    /**
     * The token payload.
     */
    private array  $payload;

    /**
     * The token signature.
     *
     * This is not used at the moment as we do not need to verify tokens.
     */
    private string $signature;

    public function __construct(
        array  $header,
        array  $payload,
        string $signature,
        string $token = "")
    {
        $this->header    = $header;
        $this->payload   = $payload;
        $this->signature = $signature;
        $this->token     = $token;
    }

    /**
     * Retrieve the full jwt token string.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Retrieve the jwt signature.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Retrieve the jwt header.
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * Retrieve the jwt payload.
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->getPayload()["exp"] < time();
    }

    /**
     * Parse a jwt.
     */
    public static function parse(?string $jwt): Jwt
    {
        Assert::notNull($jwt, "The token cannot be empty.");

        $tokens = explode(".", $jwt);

        Assert::count($tokens, 3, "Token does not have 3 sections.");

        [$head64, $body64, $signature] = $tokens;

        $head = json_decode(base64_decode($head64), true);
        $body = json_decode(base64_decode($body64), true);

        return new static($head, $body, $signature, $jwt);
    }
}
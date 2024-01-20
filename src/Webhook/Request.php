<?php
namespace Zettle\Webhook;

class Request
{
    /**
     * The request headers.
     */
    private array $headers;

    /**
     * The request body.
     */
    private ?string $content;

    /**
     * The parsed json.
     */
    private ?array $json = null;

    /**
     * Request constructor.
     */
    public function __construct(array $headers, ?string $content = null)
    {
        $this->headers = $headers;
        $this->content = $content;
    }

    /**
     * Retrieve the request body.
     */
    public function body(): string
    {
        return (string) $this->content;
    }

    /**
     * Retrieve the request json.
     */
    public function json(): array
    {
        if ($this->json === null)
            $this->json = json_decode($this->body(), true);

        return $this->json;
    }

    /**
     * Create a new request based on the php globals.
     */
    public static function make(): Request
    {
        $content = file_get_contents('php://input');
        $headers = [];

        // Init headers.

        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == "HTTP_") {
                $name = substr($name, 5);
                $name = str_replace('_', ' ', $name);
                $name = strtolower($name);
                $name = ucwords($name);
                $name = str_replace(' ', '-', $name);

                $headers[$name] = $value;
            }
            if ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            }
            if ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }

        return new static($headers, $content);
    }
}
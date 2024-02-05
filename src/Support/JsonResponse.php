<?php
namespace Zettle\Support;

use WP_HTTP_Requests_Response;

class JsonResponse extends WP_HTTP_Requests_Response
{
    /**
     * The decoded json.
     *
     * @var array|null
     */
    protected $json = null;

    /**
     * Retrieves the json content at the given path.
     */
    public function json(?string $path = null)
    {
        if ($this->json === null)
            $this->json = json_decode($this->get_data(), true);

        if ($this->json === null)
            return null;

        return is_string($path) ? Arr::get($this->json, $path) : $this->json;
    }

    /**
     * Sets the response data.
     *
     * @param string $data
     */
    public function set_data($data)
    {
        parent::set_data($data);

        $this->json = json_decode($this->get_data(), true);
    }

    /**
     * Checks if the response was successful.
     */
    public function is_successful(): bool
    {
        return $this->get_status() >= 200 &&
               $this->get_status() <= 299;
    }

    public function get_link(string $rel): ?string
    {
        $link = $this->get_headers()["link"];

        if (! $link)
            return null;

        preg_match('/<(.*)>; rel=\"'.$rel.'\",?/', $link, $matches);

        $full = $matches[0] ?? null;
        $link = $matches[1] ?? null;

        return $link;
    }

    /**
     * Extends/casts the given response into a JsonResponse.
     */
    public static function create(WP_HTTP_Requests_Response $response): self
    {
        return new JsonResponse($response->response, $response->filename,);
    }
}
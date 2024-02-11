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

    /**
     * Returns a named link from the 'Link' header.
     */
    public function get_link(string $rel): ?string
    {
        return Arr::get($this->get_links(), $rel);
    }

    /**
     * Returns the response links.
     */
    public function get_links(): array
    {
        $links = $this->get_headers()["link"];
        $links = explode(",", $links);

        $links = Arr::map($links, function ($link) {
            preg_match('/<(.*)>; rel=\"(.*)\"/', $link, $matches);

            $type = $matches[2] ?? null;
            $link = $matches[1] ?? null;

            return ["type" => $type, "link" => $link];
        });

        $links = Arr::pluck($links, "link", "type");

        return $links;
    }

    /**
     * Extends/casts the given response into a JsonResponse.
     */
    public static function create(WP_HTTP_Requests_Response $response): self
    {
        return new JsonResponse($response->response, $response->filename);
    }
}
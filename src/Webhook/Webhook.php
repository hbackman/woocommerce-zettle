<?php
namespace Zettle\Webhook;

use Zettle\Plugin;
use Zettle\Support\Arr;

abstract class Webhook
{
    /**
     * The Zettle instance.
     */
    protected Plugin $plugin;

    /**
     * The webhook event name.
     */
    public string $action;

    /**
     * Webhook constructor.
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Handle the webhook.
     */
    abstract public function handle(Request $request);

    /**
     * Unpack the message and return the payload.
     */
    protected function unpack(Request $request): array
    {
        $request = $request->json();

        $message = [
            "name" => Arr::get($request, "eventName"),
            "time" => Arr::get($request, "timestamp"),
            "uuid" => Arr::get($request, "messageUuid"),
        ];

        $payload = Arr::get($request, "payload");
        $payload = json_decode($payload, true);

        return [$payload, $message];
    }
}
<?php
namespace Zettle\Webhook;

use Zettle\Support\Arr;
use Zettle\Zettle;

abstract class Webhook
{
    /**
     * The Zettle instance.
     */
    protected Zettle $zettle;

    /**
     * The webhook event name.
     */
    public string $action;

    /**
     * Webhook constructor.
     */
    public function __construct(Zettle $zettle)
    {
        $this->zettle = $zettle;
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

        return [$payload, $message,];
    }

    /**
     * Report an error.
     */
    protected function error(string $code, ...$args): void
    {
        $message = "$code (".implode(",", $args).")";
        $context = ["source" => "woocommerce-zettle"];

        wc_get_logger()->error($message, $context);
    }
}
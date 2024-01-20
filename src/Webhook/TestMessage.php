<?php
namespace Zettle\Webhook;

use Ramsey\Uuid\Uuid;

class TestMessage extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "TestMessage";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        dd($request->json());
    }
}

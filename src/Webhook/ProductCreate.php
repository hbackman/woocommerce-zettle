<?php
namespace Zettle\Webhook;

class ProductCreate extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "ProductCreate";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        dd($request->json());
    }
}
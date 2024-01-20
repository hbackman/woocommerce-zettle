<?php
namespace Zettle\Webhook;

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
}
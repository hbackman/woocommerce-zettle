<?php
namespace Zettle\Commands;

use Webmozart\Assert\Assert;
use WP_CLI_Command;
use Zettle\Plugin;
use Zettle\Support\Arr;
use Zettle\Webhook\Request;

class RunWebhook extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(array $args = []): void
    {
        Assert::notEmpty($hook = Arr::get($args, 0), "Hook must be provided.");
        Assert::notEmpty($uuid = Arr::get($args, 1), "UUID must be provided.");

        Plugin::instance()->without_stock_update(function () use ($hook, $uuid) {
            $request = new Request([], json_encode([
                "payload" => json_encode([
                    "uuid" => $uuid,
                ]),
            ]));

            foreach (Plugin::instance()->webhooks as $webhook) {
                $webhook = new $webhook(Plugin::instance());

                if ($webhook->action == $hook)
                    $webhook->handle($request);
            }
        });
    }
}
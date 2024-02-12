<?php
namespace Zettle\Commands;

use WP_CLI_Command;
use Zettle\Plugin;
use Zettle\Support\Arr;

class GetInventories extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(array $args = []): void
    {
        $asJson = Arr::contains($args, "format=json");

        $inventories = Plugin::instance()
            ->zettle()
            ->get_inventories();

        if ($asJson) {
            echo json_encode($inventories);
            echo PHP_EOL;
        }
        else {
            dd($inventories);
        }
    }
}
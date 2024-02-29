<?php
namespace Zettle\CLI;

use WP_CLI_Command;
use Zettle\Plugin;
use Zettle\Support\Arr;

class GetLibrary extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(array $args = []): void
    {
        $asJson = Arr::contains($args, "format=json");

        $products = Plugin::instance()
            ->zettle()
            ->get_library_products();

        if ($asJson) {
            echo json_encode($products);
            echo PHP_EOL;
        }
        else {
            dd($products);
        }
    }
}
<?php
namespace Zettle\Commands;

use WP_CLI_Command;
use Zettle\Plugin;

class GetProductLibrary extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(): void
    {
        $products = Plugin::instance()
            ->zettle()
            ->get_library_products();

        dd(json_encode($products));
    }
}
<?php
namespace Zettle\Commands;

use WP_CLI_Command;
use Zettle\Jobs\MatchProductsBySku;
use Zettle\Plugin;

class MatchProducts extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(): void
    {
        (new MatchProductsBySku(Plugin::instance()))->handle();
    }
}
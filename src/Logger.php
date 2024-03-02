<?php
namespace Zettle;

use Webmozart\Assert\Assert;
use WC_Log_Levels;

/**
 * @method void emergency(string $message, ...$args)
 * @method void alert    (string $message, ...$args)
 * @method void critical (string $message, ...$args)
 * @method void error    (string $message, ...$args)
 * @method void warning  (string $message, ...$args)
 * @method void notice   (string $message, ...$args)
 * @method void info     (string $message, ...$args)
 * @method void debug    (string $message, ...$args)
 */
class Logger
{
    /**
     * Enable debug logging.
     */
    private bool $debug;

    /**
     * The plugin instance.
     */
    private Plugin $plugin;

    /**
     * Logger constructor.
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->debug = defined("WP_DEBUG") && WP_DEBUG;
    }

    /**
     * skidaddle skidoodle logging
     */
    public function __call(string $method, array $params)
    {
        // We cant skidaddle skidoodle without a valid method, so throw a bad
        // method exception if it wasn't matched.
        Assert::minCount($params, 1);

        // Build and save a log.
        $message = array_shift($params);
        $context = $params;

        $message .= " (".print_r($context, true).")";

        if ($this->debug) {
            switch ($method) {
                case WC_Log_Levels::EMERGENCY:
                case WC_Log_Levels::ALERT:
                case WC_Log_Levels::CRITICAL:
                case WC_Log_Levels::ERROR:
                    $type = "error"; break;
                case WC_Log_Levels::WARNING:
                    $type = "warning"; break;
                case WC_Log_Levels::NOTICE:
                case WC_Log_Levels::INFO:
                case WC_Log_Levels::DEBUG:
                    $type = "info"; break;
                default:
                    $type = "";
            }

            $this->plugin->push_notice($type, $message);
        }

        wc_get_logger()->log($method, $message, ["source" => "wc-zettle"]);
    }
}
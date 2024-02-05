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
    private array $debug_logs = [];
    private bool  $debug_logging = false;

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        if (defined("WP_DEBUG") && WP_DEBUG) {
            $this->init_debug_notices();
        }
    }

    /**
     * Enable the logger to print notices when a log is submitted.
     */
    private function init_debug_notices(): void
    {
        $this->debug_logging = true;

        add_action("admin_notices", function () {
            foreach ($this->debug_logs as [$method, $message]) {
                switch ($method) {
                    case WC_Log_Levels::EMERGENCY:
                    case WC_Log_Levels::ALERT:
                    case WC_Log_Levels::CRITICAL:
                    case WC_Log_Levels::ERROR:
                        $class = "notice-error"; break;
                    case WC_Log_Levels::WARNING:
                        $class = "notice-warning"; break;
                    case WC_Log_Levels::NOTICE:
                    case WC_Log_Levels::INFO:
                    case WC_Log_Levels::DEBUG:
                        $class = "notice-info"; break;
                    default:
                        $class = "";
                }
                printf(
                    '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
                    esc_attr($class),
                    esc_html($message)
                );
            }
        });
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

        // Save the log message if debug logging is enabled.
        if ($this->debug_logging) {
            $this->debug_logs[] = [$method, $message];
        }

        wc_get_logger()->log($method, $message, ["source" => "wc-zettle"]);
    }
}
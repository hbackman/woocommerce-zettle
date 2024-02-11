<?php
namespace Zettle
{
    $mock_cache = [];

    function update_option($option, $value)
    {
        global $mock_cache;
        $mock_cache[$option] = $value;
    }

    function get_option($option)
    {
        global $mock_cache;
        return $mock_cache[$option];
    }
}

namespace Zettle\Test\Unit
{
    use Zettle\Plugin;
    use Zettle\Test\TestCase;

    class PluginTest extends TestCase
    {
        public function testSingleton()
        {
            $this->assertSame(
                Plugin::instance(),
                Plugin::instance()
            );
        }

        public function testGetWebhookUrl()
        {
            $website = "http://example.com";
            $endpoint = "/wp-admin/admin-ajax.php?action=zettle_webhook";

            \Zettle\update_option("wc_zettle_webhook_url", $website);

            $this->assertEquals("$website$endpoint", Plugin::instance()->get_webhook_url());
        }

        public function testGetZettleClientIdAndSecret()
        {
            \Zettle\update_option("wc_zettle_client_id", "hello");
            \Zettle\update_option("wc_zettle_client_secret", "world");

            $this->assertEquals("hello", Plugin::instance()->get_zettle_client_id());
            $this->assertEquals("world", Plugin::instance()->get_zettle_client_secret());
        }
    }
}
<?php
namespace Zettle\Webhook
{
    /** @see \wc_get_product_id_by_zettle_uuid */
    function wc_get_product_id_by_zettle_uuid(string $uuid)
    {
        return false;
    }

    /** @see \wc_get_product_id_by_sku */
    function wc_get_product_id_by_sku($sku)
    {
        return false;
    }
}

namespace Zettle\Test\Unit\Webhook
{
    use Zettle\Test\TestCase;

    class ProductCreateTest extends TestCase
    {
        public function testCreateSimple()
        {

        }

        public function testCreateVariant()
        {

        }
    }
}
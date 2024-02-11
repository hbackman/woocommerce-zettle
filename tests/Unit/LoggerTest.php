<?php
namespace Zettle
{
    use Zettle\Test\Unit\LoggerTest;

    function wc_get_logger()
    {
        return LoggerTest::$wc_get_logger;
    }
}

namespace Zettle\Test\Unit
{
    use Mockery;
    use Zettle\Logger;
    use Zettle\Test\TestCase;

    class LoggerTest extends TestCase
    {
        public static $wc_get_logger;

        public function setUp(): void
        {
            self::$wc_get_logger = Mockery::mock(wc_get_logger())->makePartial();
        }

        public function testEmergency()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("emergency");

            (new Logger())->emergency("test");
        }

        public function testAlert()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("alert");

            (new Logger())->alert("test");
        }

        public function testCritical()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("critical");

            (new Logger())->critical("test");
        }

        public function testError()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("error");

            (new Logger())->error("test");
        }

        public function testWarning()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("warning");

            (new Logger())->warning("test");
        }

        public function testNotice()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("notice");

            (new Logger())->notice("test");
        }

        public function testInfo()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("info");

            (new Logger())->info("test");
        }

        public function testDebug()
        {
            self::$wc_get_logger
                ->shouldReceive("log")
                ->once()
                ->withSomeOfArgs("debug");

            (new Logger())->debug("test");
        }
    }
}
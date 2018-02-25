<?php
namespace KS;

class AbstractExecutableTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->config = new Test\ExecutableConfig();
        $this->app = new Test\Executable($this->config);
    }

    public function testLogging()
    {
        $log = '/tmp/test.log';
        $f = fopen($log, 'w');
        $this->app->testLog("Test1", LOG_ERR, $f);
        $this->assertContains("Test1", file_get_contents($log));
    }
}


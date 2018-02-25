<?php
namespace KS\Test;

class Executable extends \KS\AbstractExecutable
{
    public function run()
    {
        sleep(1);
    }

    public function testLog($str, $level, $dest, $force = false)
    {
        return $this->log($str, $level, $dest, $force);
    }
}


<?php
namespace KS;

abstract class SocketTestCase extends \PHPUnit\Framework\TestCase
{
    private function getTimeout($timeout)
    {
        if ($timeout===null) {
            return new TimeDuration(TimeDuration::SECONDS, 1);
        }
        else {
            return $timeout;
        }
    }

    protected function hasConnected($socket, $timeout=null)
    {
        $timeout = $this->getTimeout($timeout);
        $readList = NULL;
        $writeList = array($socket->getRawSocket());
        $exceptList = NULL;
        return \socket_select($readList, $writeList, $exceptList, $timeout->getSeconds(), $timeout->getMicros()%100000)>0;
    }

    protected function isReadReady($socket, $timeout=null)
    {
        $timeout = $this->getTimeout($timeout);
        $readList = array($socket->getRawSocket());
        $writeList = NULL;
        $exceptList = NULL;
        return \socket_select($readList, $writeList, $exceptList, $timeout->getSeconds(), $timeout->getMicros()%100000)>0;
    }

};
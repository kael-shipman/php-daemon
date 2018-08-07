<?php
namespace KS;

class SelectSocketLoop
{
    private $watchedSockets = Array();
    private $watchedSocketObjects = Array();

    // Currently only supports watching read events

    public function watchForRead($socketObject)
    {
        $rawSocket = $this->getRawSocket($socketObject);
        \array_push($this->watchedSockets, $rawSocket);
        $this->watchedSocketObjects[$this->getSocketKey($rawSocket)] = $socketObject;
    }

    public function unwatchForRead($socketObject)
    {
        $rawSocket = $this->getRawSocket($socketObject);
        if (($key = array_search($rawSocket, $this->watchedSockets)) !== false) {
            unset($this->watchedSockets[$key]);
        }
        unset($this->watchedSocketObjects[$this->getSocketKey($rawSocket)]);
    }

    public function getReadWatchCount()
    {
        return \count($this->watchedSockets);
    }

    private function getRawSocket($socket)
    {
        if (\is_a($socket, "\\KS\\BaseSocket")) {
            return $socket->getRawSocket();
        }
        return $socket;
    }

    private function getSocketKey($socket)
    {
        return \intval($socket);
    }

    public function waitForEvents($timeDuration)
    {
        $watchedReadSockets = $this->watchedSockets;
        $watchedWriteSockets = NULL;
        $watchedExceptionSockets = NULL;

        $socketEventCount = \socket_select($watchedReadSockets, $watchedWriteSockets, $watchedExceptionSockets, $timeDuration->getSeconds(), $timeDuration->getMicros()%1000000);

        if ($socketEventCount === false) {
            return Result::FAILED;
        }
        $result = Array();
        foreach ($watchedReadSockets as $rawSocket) {
            \array_push($result, $this->watchedSocketObjects[$this->getSocketKey($rawSocket)]);
        }
        return $result;
    }
}

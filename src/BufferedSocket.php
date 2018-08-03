<?php
namespace KS;

include 'BaseSocket.php';

class BufferedSocket extends BaseSocket
{
    private $buffer="";

    public const SUCCEEDED=1;
    public const FAILED=0;

    public function __construct($socket)
    {
        parent::__construct($socket);
    }

    public function processReadReady()
    {
        $data = parent::readData();
        if ($data === false)
            return FAILED;
        if (\strlen($data)===0)
            $this->socket = 0; // Socket is closed
        $this->buffer .= $data;
        return SUCCEEDED;
    }

    public function getReadBuffer()
    {
        return $this->buffer;
    }

    public function consumeReadBuffer($bytesToConsume)
    {
        $this->buffer = \substr($this->buffer, $bytesToConsume);
    }

    public function getBufferLength()
    {
        return \strlen($this->buffer);
    }
}
<?php
namespace KS;

class BufferedSocket extends BaseSocket
{
    private $buffer="";

    public function processReadReady()
    {
        $data = parent::readData();
        if ($data === false)
            return Result::FAILED;
        if (\strlen($data)===0)
            $this->socket = 0; // Socket is closed
        $this->buffer .= $data;
        return Result::SUCCEEDED;
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
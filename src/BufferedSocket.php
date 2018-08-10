<?php
namespace KS;

class BufferedSocket extends BaseSocket
{
    private $buffer="";

    public function processReadReady()
    {
        $data = parent::readData();
        if ($data === false) {
            return Result::FAILED;
        }
        if (\strlen($data)===0) {
            $this->invalidateSocket();
        }
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

    // This is a convenience method for when you know the exact size of the message
    public function getAndConsumeReadBuffer($bytes)
    {
        if ($this->getBufferLength()<$bytes) {
            throw new \RuntimeException("BufferedSocket::getAndConsumeReadBuffer requested more bytes than are currently available. Requested=".$bytes.", available=".$this->getBufferLength().".");
        }
        $data = \substr($this->getReadBuffer(), 0, $bytes);
        $this->consumeReadBuffer($bytes);
        return $data;
    }

    public function getBufferLength()
    {
        return \strlen($this->buffer);
    }

    public function clear()
    {
        $this->consumeReadBuffer($this->getBufferLength());
    }
}

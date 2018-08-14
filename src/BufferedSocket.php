<?php
namespace KS;

class BufferedSocket extends BaseSocket
{
    private $buffer="";
    private $socketClosed=false;

    public function processReadReady() : int
    {
        $data = parent::readData();
        if ($data === false) {
            $socketClosed = true;
            return Result::FAILED;
        }
        if (\strlen($data)===0) {
            $this->socketClosed = true;
        }
        $this->buffer .= $data;
        return Result::SUCCEEDED;
    }

    public function isSocketClosed() : bool
    {
        return $this->socketClosed;
    }

    public function getReadBuffer() : string
    {
        return $this->buffer;
    }

    public function reduceReadBuffer(int $bytesToReduce) : void
    {
        $this->buffer = \substr($this->buffer, $bytesToReduce);
    }

    // This is a convenience method for when you know the exact size of the message
    public function consumeReadBuffer(int $bytes) : string
    {
        if ($this->getBufferLength()<$bytes) {
            throw new \RuntimeException("BufferedSocket::getAndConsumeReadBuffer requested more bytes than are currently available. Requested=".$bytes.", available=".$this->getBufferLength().".");
        }
        $data = \substr($this->getReadBuffer(), 0, $bytes);
        $this->reduceReadBuffer($bytes);
        return $data;
    }

    public function getBufferLength() : int
    {
        return \strlen($this->buffer);
    }

    public function clear() : void
    {
        $this->reduceReadBuffer($this->getBufferLength());
    }
}

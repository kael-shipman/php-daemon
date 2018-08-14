<?php

namespace KS;

interface SocketHandlerInterface
{
    public function preRun() : void;
    public function onListen() : void;
    public function onConnect(BaseSocket $socket) : void;
    public function preProcessMessage(BaseSocket $socket, string $msg) : void;
    public function preSendResponse(BaseSocket $socket, $msg) : void;

    /**
     * @param BaseSocket $socket
     * @param string|array $response
     * @return void
     */
    public function postSendResponse(BaseSocket $socket, string $response) : void;
    public function preDisconnect(BaseSocket $socket) : void;
    public function postDisconnect() : void;
    public function preShutdown() : void;
    public function postShutdown() : void;
}
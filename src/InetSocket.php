<?php
namespace KS;

class InetSocket extends BaseSocket
{
    public static function newInetSocket($socketType=\SOCK_STREAM, $socketProtocol=\SOL_TCP)
    {
        $newSocket = parent::newRawSocket(\AF_INET, $socketType, $socketProtocol);
        return new InetSocket($newSocket);
    }

    public function getPort()
    {
        $address="";
        $port=0;
        \socket_getsockname($this->socket, $address, $port);
        return $port;
    }

    public function connect($url)
    {
        $address = \parse_url($url, PHP_URL_HOST);
        if ($address === false) {
            return Result::Failed;
        }
        $address = \gethostbyname($address);
        $port = \parse_url($url, PHP_URL_PORT);

        $result = \socket_connect($this->socket, $address, $port);

        return $result?Result::SUCCEEDED:Result::FAILED;
    }

    public function bind($address, $port)
    {
        if (\socket_bind($this->socket, $address, $port) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function accept()
    {
        return new InetSocket(\socket_accept($this->socket));
    }

    public function listen($maxConnectionAttempts=5)
    {
        if (\socket_listen($this->socket, $maxConnectionAttempts) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

}

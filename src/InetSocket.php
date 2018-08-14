<?php
namespace KS;

class InetSocket extends BaseSocket
{
    public static function createInetSocket(int $socketType=\SOCK_STREAM, int $socketProtocol=\SOL_TCP) : InetSocket
    {
        $newSocket = parent::createRawSocket(\AF_INET, $socketType, $socketProtocol);
        return new InetSocket($newSocket);
    }

    public function getPort() : int
    {
        $address="";
        $port=0;
        \socket_getsockname($this->socket, $address, $port);
        return $port;
    }

    public function connect(string $url) : int
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

    public function bind(string $address, int $port)
    {
        if (\socket_bind($this->socket, $address, $port) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function accept() : InetSocket
    {
        return new InetSocket(\socket_accept($this->socket));
    }

    public function listen(int $maxConnectionAttempts=5) : int
    {
        if (\socket_listen($this->socket, $maxConnectionAttempts) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

}

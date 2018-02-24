<?php
namespace KS;
declare(ticks = 1);


abstract class AbstractSocketDaemon extends AbstractDaemon
{
    private $sock;
    private $cnx;
    private $initialized = false;

    public function run()
    {
        if (!$this->initialized) {
            $this->init();
            $this->initialized = true;
        }
        $this->preRun();
        try {
            // Set up the socket
            if (($this->sock = \socket_create($this->config->getSocketDomain(), $this->config->getSocketType(), $this->config->getSocketProtocol())) === false) {
                throw new \RuntimeException("Couldn't establish a socket connection: ".\socket_strerror(\socket_last_error()));
            }
            if (\socket_bind($this->sock, $this->config->getSocketAddress(), $this->config->getSocketPort()) === false) {
                throw new \RuntimeException("Couldn't bind to socket at {$this->config->getSocketAddress()} ({$this->config->getSocketPort()}): " . \socket_strerror(\socket_last_error($this->sock)));
            }
            if (\socket_listen($this->sock, 5) === false) {
                throw new \RuntimeException("Failed to listen on socket: ".\socket_strerror(\socket_last_error($this->sock)));
            }

            $this->onListen();

            // Establish communication loop
            $shuttingDown = false;
            do {
                if (($this->cnx = \socket_accept($this->sock)) === false) {
                    throw new \RuntimeException("Error waiting for connections: ".\socket_strerror(\socket_last_error($this->sock)));
                }
                $this->onConnect();
                $buffer = '';
                do {
                    if (false === ($chunk = \socket_read($this->cnx, 2048, PHP_NORMAL_READ))) {
                        throw new \RuntimeException("Socket read from peer failed: ".\socket_strerror(\socket_last_error($this->sock)));
                    }

                    $origLen = strlen($chunk);
                    $chunk = trim($chunk, "\n\r");
                    $buffer .= $chunk;

                    // If we've received a line break, time to process
                    if ($origLen > strlen($chunk)) {
                        try {
                            $this->preProcessMessage($buffer);
                            $response = $this->processMessage($buffer);
                            $this->preSendResponse($response);
                            if ($response) {
                                $this->write($response);
                            }
                            $this->postSendResponse();
                        } catch (Exception\ConnectionClose $e) {
                            $this->preDisconnect();
                            break;
                        } catch (Exception\Shutdown $e) {
                            $shuttingDown = true;
                            $this->preDisconnect();
                            break;
                        } catch (Exception\UserMessage $e) {
                            $jsonapi = [
                                'errors' => [
                                    [
                                        'status' => $e->getCode(),
                                        'title' => 'Error',
                                        'detail' => $e->getMessage(),
                                    ]
                                ]
                            ];
                            $this->preSendResponse($jsonapi);
                            $jsonapi = json_encode($jsonapi);
                            $this->write($jsonapi);
                            $this->postSendResponse();
                        }
                        $buffer = '';
                    }
                } while (true);
                \socket_close($this->cnx);
                $this->postDisconnect();
            } while (!$shuttingDown);
            $this->preShutdown();
            $this->shutdown();
            $this->postShutdown();
        } catch (\Throwable $e) {
            $this->preShutdown();
            $this->shutdown();
            $this->postShutdown();
            throw $e;
        }
    }

    protected function init()
    {
        // To be overridden
    }

    abstract protected function processMessage(string $msg) : ?string;

    protected function write(string $msg) : void
    {
        \socket_write($this->cnx, $msg, strlen($msg));
    }

    public function shutdown()
    {
        $this->log("Shutting down", LOG_INFO, null, true);
        if ($this->cnx) {
            \socket_close($this->cnx);
        }
        if ($this->sock) {
            \socket_close($this->sock);
        }
        if ($this->config->getSocketDomain() === AF_UNIX && file_exists($this->config->getSocketAddress())) {
            $this->log("Cleaning up Unix Socket", LOG_INFO);
            unlink($this->config->getSocketAddress());
        }
    }





    // Hooks

    protected function preRun()
    {
        $this->log("Starting up...", LOG_INFO);
    }

    protected function onListen()
    {
        $msg = "Listening on {$this->config->getSocketAddress()}";
        if ($p = $this->config->getSocketPort()) {
            $msg .= ":$p";
        }
        $this->log($msg, LOG_INFO);
    }

    protected function onConnect()
    {
        $this->log("Connected to peer", LOG_DEBUG);
    }

    protected function preProcessMessage(string $msg)
    {
        $this->log("Got a message: $msg", LOG_DEBUG);
    }

    protected function preSendResponse($msg)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->log("Got a response: $msg", LOG_DEBUG);
    }

    protected function postSendResponse()
    {
        $this->log("Response sent.", LOG_DEBUG);
    }

    protected function preDisconnect()
    {
        $this->log("Disconnecting from peer", LOG_DEBUG);
    }

    protected function postDisconnect()
    {
        $this->log("Disconnected. Waiting.", LOG_DEBUG);
    }

    protected function preShutdown()
    {
        $this->log("Preparing to shutdown.", LOG_DEBUG);
    }

    protected function postShutdown()
    {
        $this->log("Goodbye.", LOG_DEBUG);
    }
}


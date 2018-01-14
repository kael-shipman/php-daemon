<?php
namespace KS;

/**
 * * Should daemonize, i.e., not return until killed
 * * Should output useful info to stdout
 * * Should output errors to stderr
 * * Should fork logging processes
 * * Should accept command line arguments
 * * Should accept an optional config file
 */

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

                    // If we've received a return, time to process
                    if ($origLen > strlen($chunk)) {
                        try {
                            $this->preProcessMessage($buffer);
                            $response = $this->processMessage($buffer);
                            $this->preSendResponse($response);
                            if ($response) {
                                $this->write($response);
                            }
                            $this->postSendResponse();
                        } catch (ConnectionCloseException $e) {
                            $this->preDisconnect();
                            break;
                        } catch (ShutdownException $e) {
                            $shuttingDown = true;
                            $this->preDisconnect();
                            break;
                        } catch (UserMessageException $e) {
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
        $this->say("\nShutting down");
        if ($this->cnx) {
            \socket_close($this->cnx);
        }
        if ($this->sock) {
            \socket_close($this->sock);
        }
        if ($this->config->getSocketDomain() === AF_UNIX && file_exists($this->config->getSocketAddress())) {
            $this->say("\nCleaning up Unix Socket", 2);
            unlink($this->config->getSocketAddress());
        }
    }





    // Hooks

    protected function preRun()
    {
        $this->say("\nStarting up...");
    }

    protected function onListen()
    {
        $msg = "\nListening on {$this->config->getSocketAddress()}";
        if ($p = $this->config->getSocketPort()) {
            $msg .= ":$p";
        }
        fwrite(STDOUT, $msg);
    }

    protected function onConnect()
    {
        $this->say("\nConnected to peer", 2);
    }

    protected function preProcessMessage(string $msg)
    {
        $this->say("\nGot a message!!", 3);
    }

    protected function preSendResponse($msg)
    {
        $this->say("\nGot a response!!!", 3);
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->say(" Message: $msg", 4);
    }

    protected function postSendResponse()
    {
        $this->say("\nResponse sent.", 3);
    }

    protected function preDisconnect()
    {
        $this->say("\nDisconnecting from peer", 3);
    }

    protected function postDisconnect()
    {
        $this->say("\nDisconnected. Waiting.", 2);
    }

    protected function preShutdown()
    {
        $this->say("\nPreparing to shutdown.", 3);
    }

    protected function postShutdown()
    {
        $this->say("\n\nGoodbye.\n");
    }
}

